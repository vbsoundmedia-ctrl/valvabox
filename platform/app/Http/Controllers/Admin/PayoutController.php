<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Services\Wallet;
use Illuminate\Http\Request;
use Throwable;

class PayoutController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $q = Payout::with('user');
        if ($status !== 'all') {
            $q->where('status', $status);
        }

        return view('admin.payouts', ['payouts' => $q->latest()->paginate(40)->withQueryString(), 'status' => $status]);
    }

    public function action(Request $request, Payout $payout, Wallet $wallet)
    {
        $data = $request->validate(['action' => ['required', 'in:paystack,paid,reject'], 'note' => ['nullable', 'string', 'max:500']]);
        abort_unless(in_array($payout->status, ['pending', 'processing'], true), 422, 'This payout is already closed.');

        try {
            match ($data['action']) {
                'paystack' => $wallet->sendViaPaystack($payout),
                'paid' => $wallet->markPaid($payout, $data['note'] ?? 'Paid manually'),
                'reject' => $wallet->refund($payout, 'rejected', $data['note'] ?? 'Rejected by admin'),
            };
        } catch (Throwable $e) {
            return back()->withErrors(['payout' => $e->getMessage()]);
        }

        return back()->with('success', 'Payout '.$payout->reference.' is now '.$payout->fresh()->status.'.');
    }
}
