@extends('layouts.app')
@section('title', 'Earnings & wallet')
@section('content')
<div class="top"><div><h1>Earnings &amp; wallet</h1><p>Royalties are credited in Naira after stores report (usually monthly, 2–3 months after streams).</p></div></div>

<div class="grid g-main">
    <div>
        <div class="card kpi dark"><small>Available balance</small><div class="num" style="font-size:36px">{{ naira($balance, true) }}</div>
            <small>Your plan: {{ $user->currentPlan()->name }} · you keep {{ $user->currentPlan()->royalty_share }}% of royalties</small></div>

        <div class="card mt">
            <h2>Transactions</h2>
            @if ($entries->isEmpty())
                <p class="muted small mt">No transactions yet.</p>
            @else
                <div class="table-wrap mt"><table>
                    <tr><th>Date</th><th>Description</th><th class="right">Amount</th></tr>
                    @foreach ($entries as $e)
                        <tr><td class="nowrap small">{{ $e->created_at->format('j M Y') }}</td><td>{{ $e->description }}</td>
                            <td class="right nowrap" style="color:{{ $e->amount_kobo < 0 ? 'var(--red)' : 'var(--green-d)' }}"><b>{{ $e->amount_kobo > 0 ? '+' : '' }}{{ naira($e->amount_kobo, true) }}</b></td></tr>
                    @endforeach
                </table></div>
                @include('partials.pagination', ['paginator' => $entries])
            @endif
        </div>
    </div>

    <div>
        <div class="card">
            <h2>Withdraw</h2>
            @if ($bank)
                <div class="list-row" style="border:1px solid var(--line);border-radius:12px;padding:12px;margin:12px 0">
                    <div><b>{{ $bank->account_name }}</b><br><span class="muted small">{{ $bank->bank_name }} · ••••{{ substr($bank->account_number, -4) }}</span></div></div>
                <form method="post" action="{{ route('wallet.withdraw') }}">@csrf
                    <label>Amount (₦)</label><input type="number" name="amount" min="{{ $min / 100 }}" step="0.01" value="{{ old('amount') }}" placeholder="{{ number_format($min / 100) }}" required>
                    <div class="help">Minimum {{ naira($min) }} · Fee {{ naira($fee) }} · Usually arrives the same day.</div>
                    <button class="btn btn-dark btn-block mt" type="submit" @disabled($balance < $min + $fee)>Withdraw to {{ $bank->bank_name }}</button>
                </form>
            @else
                <p class="muted small mt">Add your bank account below to withdraw.</p>
            @endif
        </div>

        <form class="card mt" method="post" action="{{ route('wallet.bank') }}">@csrf
            <h2>{{ $bank ? 'Change bank account' : 'Add bank account' }}</h2>
            <p class="muted small mb">Nigerian (NGN) accounts only. The account name should match your name.</p>
            @if ($banks)
                <label>Bank</label><select name="bank_code" required><option value="">Choose your bank…</option>
                    @foreach ($banks as $b)<option value="{{ $b['code'] }}" @selected(old('bank_code', $bank?->bank_code) === $b['code'])>{{ $b['name'] }}</option>@endforeach</select>
                <label class="mt">Account number</label><input type="text" name="account_number" inputmode="numeric" maxlength="10" value="{{ old('account_number') }}" required>
                <div class="help">We’ll check the account name with your bank automatically.</div>
            @else
                <label>Bank name</label><input type="text" name="bank_name" value="{{ old('bank_name') }}" required>
                <label class="mt">Account number</label><input type="text" name="account_number" inputmode="numeric" maxlength="10" value="{{ old('account_number') }}" required>
                <label class="mt">Account name</label><input type="text" name="account_name" value="{{ old('account_name') }}" required>
            @endif
            <button class="btn btn-light btn-block mt" type="submit">Save bank account</button>
        </form>

        @if ($payouts->isNotEmpty())
            <div class="card mt"><h2>Recent withdrawals</h2>
                @foreach ($payouts as $p)
                    <div class="list-row"><span class="small">{{ $p->created_at->format('j M') }} · {{ naira($p->amount_kobo) }}</span><span class="badge {{ status_class($p->status) }}">{{ ucfirst($p->status) }}</span></div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
