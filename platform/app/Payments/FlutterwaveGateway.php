<?php

namespace App\Payments;

use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FlutterwaveGateway implements PaymentGateway
{
    private const BASE = 'https://api.flutterwave.com/v3';

    public function name(): string
    {
        return 'flutterwave';
    }

    public function isConfigured(): bool
    {
        return $this->secret() !== '';
    }

    private function secret(): string
    {
        return (string) setting('flutterwave_secret', '');
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->secret())->acceptJson()->timeout(30)->baseUrl(self::BASE);
    }

    private function data($response): array
    {
        $json = $response->json() ?? [];
        if (! $response->successful() || ($json['status'] ?? '') !== 'success') {
            throw new RuntimeException('Flutterwave: '.($json['message'] ?? 'request failed (HTTP '.$response->status().')'));
        }

        return $json['data'] ?? [];
    }

    public function initialize(Payment $payment, string $callbackUrl): string
    {
        $data = $this->data($this->http()->post('/payments', [
            'tx_ref' => $payment->reference,
            'amount' => $payment->amount_kobo / 100,
            'currency' => 'NGN',
            'redirect_url' => $callbackUrl,
            'payment_options' => 'card,banktransfer,ussd',
            'customer' => ['email' => $payment->user->email, 'name' => $payment->user->name, 'phonenumber' => $payment->user->phone],
            'customizations' => ['title' => setting('site_name'), 'description' => $payment->description],
            'meta' => ['purpose' => $payment->purpose, 'purpose_id' => $payment->purpose_id],
        ]));

        return $data['link'];
    }

    public function verify(string $reference): ?int
    {
        $data = $this->data($this->http()->get('/transactions/verify_by_reference', ['tx_ref' => $reference]));

        return ($data['status'] ?? null) === 'successful' && ($data['currency'] ?? '') === 'NGN'
            ? (int) round(((float) $data['amount']) * 100) : null;
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $hash = (string) setting('flutterwave_hash', '');
        $given = (string) $request->header('verif-hash');

        return $hash !== '' && $given !== '' && hash_equals($hash, $given);
    }

    public function parseWebhook(array $payload): array
    {
        return [$payload['event'] ?? null, $payload['data']['tx_ref'] ?? null];
    }
}
