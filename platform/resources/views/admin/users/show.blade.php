@extends('layouts.app')
@section('title', $user->displayName())
@section('content')
<div class="top"><div><h1>{{ $user->displayName() }}</h1><p>{{ $user->name }} · {{ $user->email }} · {{ $user->phone }} · joined {{ $user->created_at->format('j M Y') }}</p></div>
    <a class="btn btn-light" href="{{ route('admin.users.index') }}">← Users</a></div>
<div class="grid g3">
    <div class="card kpi dark"><small>Wallet balance</small><div class="num">{{ naira($balance, true) }}</div></div>
    <div class="card kpi"><small>Plan</small><div class="num" style="font-size:22px">{{ $user->currentPlan()->name }}</div><span class="small muted">{{ $user->hasActivePlan() ? 'until '.$user->plan_expires_at->format('j M Y') : 'free plan' }}</span></div>
    <div class="card kpi"><small>Bank account</small><div class="small mt">@if ($user->bankAccount){{ $user->bankAccount->account_name }}<br>{{ $user->bankAccount->bank_name }} · {{ $user->bankAccount->account_number }}@else — @endif</div></div>
</div>
<div class="grid g2 mt">
    <form class="card" method="post" action="{{ route('admin.users.update', $user) }}">@csrf @method('PUT')
        <h2>Account</h2>
        <div class="form-grid mt">
            <div><label>Role</label><select name="role"><option value="artist" @selected($user->role === 'artist')>Artist</option><option value="admin" @selected($user->role === 'admin')>Admin</option></select></div>
            <div><label>Status</label><select name="status"><option value="active" @selected($user->status === 'active')>Active</option><option value="suspended" @selected($user->status === 'suspended')>Suspended</option></select></div>
            <div><label>Paid plan</label><select name="plan_id"><option value="">None (free)</option>@foreach ($plans->where('price_kobo', '>', 0) as $p)<option value="{{ $p->id }}" @selected($user->plan_id === $p->id)>{{ $p->name }}</option>@endforeach</select></div>
            <div><label>Plan expires</label><input type="date" name="plan_expires_at" value="{{ $user->plan_expires_at?->toDateString() }}"></div>
        </div>
        <button class="btn btn-dark mt">Save</button>
    </form>
    <form class="card" method="post" action="{{ route('admin.users.adjust', $user) }}" data-confirm="Adjust this wallet?">@csrf
        <h2>Manual wallet adjustment</h2>
        <p class="muted small">Positive to credit, negative to debit (e.g. -5000). Recorded in the ledger.</p>
        <div class="form-grid mt"><div><label>Amount (₦)</label><input type="number" step="0.01" name="amount" required></div><div><label>Reason</label><input type="text" name="description" required></div></div>
        <button class="btn btn-light mt">Apply</button>
    </form>
</div>
<div class="grid g2 mt">
    <div class="card"><h2>Releases</h2>
        @forelse ($releases as $r)<div class="list-row"><a href="{{ route('admin.releases.show', $r) }}">{{ $r->title }}</a><span class="badge {{ status_class($r->status) }}">{{ $r->statusLabel() }}</span></div>
        @empty<p class="muted small mt">None.</p>@endforelse</div>
    <div class="card"><h2>Ledger</h2>
        @forelse ($ledger as $e)<div class="list-row"><span class="small">{{ $e->created_at->format('j M Y') }} · {{ $e->description }}</span><b class="nowrap">{{ naira($e->amount_kobo, true) }}</b></div>
        @empty<p class="muted small mt">No entries.</p>@endforelse</div>
</div>
<div class="card mt"><h2>Payments</h2>
    @forelse ($payments as $p)<div class="list-row"><span class="small">{{ $p->created_at->format('j M Y') }} · {{ $p->description }} · {{ $p->reference }}</span><span><b>{{ naira($p->amount_kobo) }}</b> <span class="badge {{ status_class($p->status) }}">{{ $p->status }}</span></span></div>
    @empty<p class="muted small mt">None.</p>@endforelse</div>
@endsection
