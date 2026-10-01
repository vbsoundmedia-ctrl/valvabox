<?php

namespace App\Payments;

use App\Models\Payment;
use App\Models\Payout;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackGateway implements PaymentGateway
{
    private const BASE = 'https://api.paystack.co';

    public function name(): string
    {
        return 'paystack';
    }

    public function isConfigured(): bool
    {
        return $this->secret() !== '';
    }

    private function secret(): string
    {
        return (string) setting('paystack_secret', '');
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->secret())->acceptJson()->timeout(30)->baseUrl(self::BASE);
    }

    private function data($response): array
    {
        $json = $response->json() ?? [];
        if (! $response->successful() || ! ($json['status'] ?? false)) {
            throw new RuntimeException('Paystack: '.($json['message'] ?? 'request failed (HTTP '.$response->status().')'));
        }

        return $json['data'] ?? [];
    }

    public function initialize(Payment $payment, string $callbackUrl): string
    {
        $data = $this->data($this->http()->post('/transaction/initialize', [
            'email' => $payment->user->email,
            'amount' => $payment->amount_kobo,
            'currency' => 'NGN',
            'reference' => $payment->reference,
            'callback_url' => $callbackUrl,
            'metadata' => ['purpose' => $payment->purpose, 'purpose_id' => $payment->purpose_id, 'user_id' => $payment->user_id],
        ]));

        return $data['authorization_url'];
    }

    public function verify(string $reference): ?int
    {
        $data = $this->data($this->http()->get('/transaction/verify/'.rawurlencode($reference)));

        return ($data['status'] ?? null) === 'success' && ($data['currency'] ?? 'NGN') === 'NGN'
            ? (int) $data['amount'] : null;
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = (string) $request->header('x-paystack-signature');

        return $signature !== '' && $this->isConfigured()
            && hash_equals(hash_hmac('sha512', $request->getContent(), $this->secret()), $signature);
    }

    public function parseWebhook(array $payload): array
    {
        return [$payload['event'] ?? null, $payload['data']['reference'] ?? null];
    }

    // ---- Bank & transfer helpers (artist withdrawals) ----

    public function banks(): array
    {
        return cache()->remember('paystack_banks', now()->addDay(), function () {
            return collect($this->data($this->http()->get('/bank', ['country' => 'nigeria', 'currency' => 'NGN', 'perPage' => 200])))
                ->map(fn ($b) => ['code' => $b['code'], 'name' => $b['name']])
                ->unique('code')->sortBy('name')->values()->all();
        });
    }

    public function resolveAccount(string $accountNumber, string $bankCode): string
    {
        $data = $this->data($this->http()->get('/bank/resolve', ['account_number' => $accountNumber, 'bank_code' => $bankCode]));

        return $data['account_name'];
    }

    public function createRecipient(string $name, string $accountNumber, string $bankCode): string
    {
        $data = $this->data($this->http()->post('/transferrecipient', [
            'type' => 'nuban', 'name' => $name, 'account_number' => $accountNumber,
            'bank_code' => $bankCode, 'currency' => 'NGN',
        ]));

        return $data['recipient_code'];
    }

    /** Returns the transfer status reported by Paystack (success, pending, otp, ...). */
    public function transfer(Payout $payout, string $recipientCode): array
    {
        $data = $this->data($this->http()->post('/transfer', [
            'source' => 'balance',
            'amount' => $payout->amount_kobo,
            'recipient' => $recipientCode,
            'reason' => 'Valvabox royalties',
            'reference' => $payout->reference,
        ]));

        return ['status' => $data['status'] ?? 'pending', 'transfer_code' => $data['transfer_code'] ?? null];
    }
}
