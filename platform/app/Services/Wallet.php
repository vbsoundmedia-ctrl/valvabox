<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\Payout;
use App\Models\User;
use App\Payments\PaystackGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class Wallet
{
    public function credit(User $user, int $kobo, string $type, string $description, ?object $ref = null): LedgerEntry
    {
        return LedgerEntry::create([
            'user_id' => $user->id, 'type' => $type, 'amount_kobo' => $kobo, 'description' => $description,
            'ref_type' => $ref ? class_basename($ref) : null, 'ref_id' => $ref?->id,
        ]);
    }

    /** Create a withdrawal request and debit the wallet immediately so the money can't be spent twice. */
    public function requestWithdrawal(User $user, int $amountKobo): Payout
    {
        $bank = $user->bankAccount;
        if (! $bank) {
            throw new RuntimeException('Add your bank account first.');
        }
        $min = (int) setting('min_withdrawal_kobo');
        if ($amountKobo < $min) {
            throw new RuntimeException('The minimum withdrawal is '.naira($min).'.');
        }
        $fee = (int) setting('withdrawal_fee_kobo');

        return DB::transaction(function () use ($user, $amountKobo, $fee, $bank) {
            // Lock this user's row so two withdrawals can't race past the balance check.
            User::whereKey($user->id)->lockForUpdate()->first();
            if ($user->balanceKobo() < $amountKobo + $fee) {
                throw new RuntimeException('Insufficient balance (the '.naira($fee).' fee is included).');
            }
            $payout = Payout::create([
                'user_id' => $user->id,
                'reference' => 'vbxpo_'.strtolower(Str::random(20)),
                'amount_kobo' => $amountKobo,
                'fee_kobo' => $fee,
                'bank_name' => $bank->bank_name,
                'bank_code' => $bank->bank_code,
                'account_number' => $bank->account_number,
                'account_name' => $bank->account_name,
                'status' => 'pending',
            ]);
            $this->credit($user, -($amountKobo + $fee), 'withdrawal', 'Withdrawal to '.$bank->bank_name.' ••••'.substr($bank->account_number, -4), $payout);

            return $payout;
        });
    }

    /** Send the payout through Paystack Transfers. */
    public function sendViaPaystack(Payout $payout): void
    {
        $paystack = app(PaystackGateway::class);
        if (! $paystack->isConfigured()) {
            throw new RuntimeException('Paystack is not configured.');
        }
        if (! $payout->bank_code) {
            throw new RuntimeException('This bank account has no bank code; pay it manually.');
        }
        $bank = $payout->user->bankAccount;
        $recipient = $bank && $bank->account_number === $payout->account_number && $bank->recipient_code
            ? $bank->recipient_code
            : $paystack->createRecipient($payout->account_name, $payout->account_number, $payout->bank_code);
        if ($bank && $bank->account_number === $payout->account_number && ! $bank->recipient_code) {
            $bank->update(['recipient_code' => $recipient]);
        }

        $result = $paystack->transfer($payout, $recipient);
        $payout->update([
            'status' => $result['status'] === 'success' ? 'paid' : 'processing',
            'transfer_code' => $result['transfer_code'],
            'processed_at' => $result['status'] === 'success' ? now() : null,
            'admin_note' => $result['status'] === 'otp' ? 'Paystack needs OTP: disable transfer OTP in your Paystack dashboard, or finalise it there.' : $payout->admin_note,
        ]);
    }

    public function markPaid(Payout $payout, ?string $note = null): void
    {
        $payout->update(['status' => 'paid', 'processed_at' => now(), 'admin_note' => $note ?? $payout->admin_note]);
    }

    /** Fail/reject a payout and refund the wallet (once). */
    public function refund(Payout $payout, string $status, ?string $note = null): void
    {
        DB::transaction(function () use ($payout, $status, $note) {
            $payout = Payout::whereKey($payout->id)->lockForUpdate()->first();
            if (in_array($payout->status, ['failed', 'rejected'], true)) {
                return;
            }
            $payout->update(['status' => $status, 'processed_at' => now(), 'admin_note' => $note]);
            $this->credit($payout->user, $payout->amount_kobo + $payout->fee_kobo, 'reversal', 'Refund of withdrawal '.$payout->reference, $payout);
        });
    }
}
