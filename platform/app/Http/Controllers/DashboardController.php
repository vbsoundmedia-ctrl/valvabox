<?php

namespace App\Http\Controllers;

use App\Models\RoyaltyLine;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        if ($user->isAdmin() && ! $request->boolean('artist')) {
            return redirect()->route('admin.dashboard');
        }

        $monthly = $user->ledger()->where('type', 'royalty')->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->get(['amount_kobo', 'created_at'])
            ->groupBy(fn ($e) => $e->created_at->format('Y-m'))
            ->map->sum('amount_kobo');
        $months = collect(range(11, 0))->map(fn ($i) => now()->subMonths($i))->mapWithKeys(fn ($d) => [$d->format('M') => (int) ($monthly[$d->format('Y-m')] ?? 0)]);

        return view('artist.dashboard', [
            'user' => $user,
            'balance' => $user->balanceKobo(),
            'totalUnits' => (int) RoyaltyLine::where('user_id', $user->id)->sum('units'),
            'releaseCounts' => $user->releases()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'videoCount' => $user->videos()->count(),
            'months' => $months,
            'topStores' => RoyaltyLine::where('user_id', $user->id)->whereNotNull('store')
                ->selectRaw('store, sum(amount_kobo) total, sum(units) units')->groupBy('store')->orderByDesc('total')->limit(6)->get(),
            'recent' => $user->releases()->latest()->limit(5)->with('tracks')->get(),
        ]);
    }
}
