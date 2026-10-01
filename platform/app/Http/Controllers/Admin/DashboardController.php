<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Release;
use App\Models\User;
use App\Models\Video;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'stats' => [
                'artists' => User::where('role', 'artist')->count(),
                'new_artists' => User::where('role', 'artist')->where('created_at', '>=', now()->subDays(30))->count(),
                'revenue_month' => (int) Payment::where('status', 'success')->where('paid_at', '>=', now()->startOfMonth())->sum('amount_kobo'),
                'revenue_total' => (int) Payment::where('status', 'success')->sum('amount_kobo'),
                'in_review' => Release::where('status', 'in_review')->count() + Video::where('status', 'in_review')->count(),
                'live' => Release::where('status', 'live')->count(),
                'pending_payouts' => Payout::whereIn('status', ['pending', 'processing'])->count(),
                'pending_payouts_kobo' => (int) Payout::whereIn('status', ['pending', 'processing'])->sum('amount_kobo'),
                'wallets_kobo' => (int) LedgerEntry::sum('amount_kobo'),
            ],
            'queue' => Release::with('user')->where('status', 'in_review')->orderBy('submitted_at')->limit(8)->get(),
            'payments' => Payment::with('user')->latest()->limit(8)->get(),
            'gatewayReady' => app(\App\Payments\PaymentService::class)->gateway()->isConfigured(),
        ]);
    }
}
