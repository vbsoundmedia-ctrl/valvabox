<?php

namespace App\Http\Controllers;

use App\Payments\PaystackGateway;
use App\Services\Wallet;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class WalletController extends Controller
{
    public function index(Request $request, PaystackGateway $paystack)
    {
        $user = $request->user();
        $banks = [];
        if ($paystack->isConfigured()) {
            try {
                $banks = $paystack->banks();
            } catch (Throwable $e) {
                report($e);
            }
        }

        return view('artist.wallet', [
            'user' => $user,
            'balance' => $user->balanceKobo(),
            'bank' => $user->bankAccount,
            'banks' => $banks,
            'entries' => $user->ledger()->latest()->paginate(20, ['*'], 'entries'),
            'payouts' => $user->payouts()->latest()->limit(10)->get(),
            'min' => (int) setting('min_withdrawal_kobo'),
            'fee' => (int) setting('withdrawal_fee_kobo'),
        ]);
    }

    public function saveBank(Request $request, PaystackGateway $paystack)
    {
        $data = $request->validate([
            'bank_code' => ['nullable', 'string', 'max:20'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['required', 'digits:10'],
            'account_name' => ['nullable', 'string', 'max:150'],
        ], ['account_number.digits' => 'Enter your 10-digit NUBAN account number.']);

        if ($paystack->isConfigured() && ! empty($data['bank_code'])) {
            $bank = collect($paystack->banks())->firstWhere('code', $data['bank_code']);
            if (! $bank) {
                return back()->withErrors(['bank_code' => 'Choose your bank.']);
            }
            try {
                $data['account_name'] = $paystack->resolveAccount($data['account_number'], $data['bank_code']);
            } catch (Throwable) {
                return back()->withInput()->withErrors(['account_number' => 'We couldn’t verify that account number with the bank. Check it and try again.']);
            }
            $data['bank_name'] = $bank['name'];
        } elseif (empty($data['bank_name']) || empty($data['account_name'])) {
            return back()->withInput()->withErrors(['bank_name' => 'Enter your bank name and account name.']);
        }

        $request->user()->bankAccount()->updateOrCreate([], $data + ['recipient_code' => null]);

        return back()->with('success', 'Bank account saved: '.$data['account_name'].' · '.$data['bank_name']);
    }

    public function withdraw(Request $request, Wallet $wallet)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:1']]);
        try {
            $payout = $wallet->requestWithdrawal($request->user(), to_kobo($data['amount']));
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        if (setting('auto_transfer') === '1') {
            try {
                $wallet->sendViaPaystack($payout);
            } catch (Throwable $e) {
                report($e); // stays pending for an admin to handle
            }
        }

        return back()->with('success', 'Withdrawal of '.naira($payout->amount_kobo).' requested. You’ll get it in your bank shortly.');
    }
}
