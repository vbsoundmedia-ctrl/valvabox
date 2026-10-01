@extends('layouts.app')
@section('title', 'Admin')
@section('content')
<div class="top"><div><h1>Admin overview</h1><p>{{ now()->format('l, j F Y') }}</p></div></div>
@unless ($gatewayReady)
    <div class="alert alert-warn"><b>Payments are not set up yet.</b> Add your Paystack (or Flutterwave) secret key in <a href="{{ route('admin.settings') }}">Settings</a> so artists can pay.</div>
@endunless
<div class="grid g4">
    <div class="card kpi dark"><small>Revenue this month</small><div class="num">{{ naira($stats['revenue_month']) }}</div><small>{{ naira($stats['revenue_total']) }} all time</small></div>
    <div class="card kpi"><small>Artists</small><div class="num">{{ number_format($stats['artists']) }}</div><span class="muted small">+{{ $stats['new_artists'] }} in 30 days</span></div>
    <div class="card kpi"><small>Waiting for review</small><div class="num">{{ $stats['in_review'] }}</div><a class="small" href="{{ route('admin.releases.index') }}">Open queue →</a></div>
    <div class="card kpi"><small>Pending withdrawals</small><div class="num">{{ $stats['pending_payouts'] }}</div><span class="muted small">{{ naira($stats['pending_payouts_kobo']) }} · wallets hold {{ naira($stats['wallets_kobo']) }}</span></div>
</div>
<div class="grid g2 mt">
    <div class="card"><div class="card-head"><h2>Review queue</h2><a class="small" href="{{ route('admin.releases.index') }}">All →</a></div>
        @forelse ($queue as $r)
            <div class="list-row"><div><a href="{{ route('admin.releases.show', $r) }}"><b>{{ $r->title }}</b></a><br><span class="small muted">{{ $r->primary_artist }} · {{ $r->user->email }}</span></div>
                <span class="small muted nowrap">{{ $r->submitted_at?->diffForHumans() }}</span></div>
        @empty <p class="muted small">Nothing to review. 🎉</p> @endforelse
    </div>
    <div class="card"><div class="card-head"><h2>Latest payments</h2><a class="small" href="{{ route('admin.payments') }}">All →</a></div>
        @forelse ($payments as $p)
            <div class="list-row"><div><b>{{ naira($p->amount_kobo) }}</b> <span class="small muted">{{ $p->description }}</span><br><span class="small muted">{{ $p->user->email }}</span></div>
                <span class="badge {{ status_class($p->status) }}">{{ $p->status }}</span></div>
        @empty <p class="muted small">No payments yet.</p> @endforelse
    </div>
</div>
@endsection
