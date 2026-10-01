@extends('layouts.app')
@section('title', 'Withdrawals')
@section('content')
<div class="top"><div><h1>Withdrawals</h1><p>Artist payout requests. The amount is already deducted from their wallet; rejecting refunds it.</p></div></div>
<div class="tabs">@foreach (['pending' => 'Pending', 'processing' => 'Processing', 'paid' => 'Paid', 'failed' => 'Failed', 'rejected' => 'Rejected', 'all' => 'All'] as $k => $v)<a class="{{ $status === $k ? 'on' : '' }}" href="?status={{ $k }}">{{ $v }}</a>@endforeach</div>
<div class="card"><div class="table-wrap"><table>
    <tr><th>Requested</th><th>Artist</th><th>Bank</th><th class="right">Amount</th><th>Status</th><th>Action</th></tr>
    @forelse ($payouts as $p)
        <tr><td class="small nowrap">{{ $p->created_at->format('j M Y H:i') }}</td>
            <td class="small"><a href="{{ route('admin.users.show', $p->user) }}">{{ $p->user->displayName() }}</a><br>{{ $p->user->email }}</td>
            <td class="small"><b>{{ $p->account_name }}</b><br>{{ $p->bank_name }} · {{ $p->account_number }}</td>
            <td class="right nowrap"><b>{{ naira($p->amount_kobo, true) }}</b><br><span class="tiny muted">fee {{ naira($p->fee_kobo) }}</span></td>
            <td><span class="badge {{ status_class($p->status) }}">{{ $p->status }}</span>@if ($p->admin_note)<div class="tiny muted">{{ $p->admin_note }}</div>@endif</td>
            <td>@if (in_array($p->status, ['pending', 'processing']))
                <form method="post" action="{{ route('admin.payouts.action', $p) }}" class="row" style="gap:6px">@csrf
                    @if ($p->status === 'pending' && $p->bank_code)<button class="btn btn-green btn-sm" name="action" value="paystack" onclick="return confirm('Send {{ naira($p->amount_kobo) }} via Paystack now?')">Send via Paystack</button>@endif
                    <button class="btn btn-light btn-sm" name="action" value="paid" onclick="return confirm('Mark as paid (you paid it manually)?')">Mark paid</button>
                    <button class="btn btn-danger btn-sm" name="action" value="reject" onclick="return confirm('Reject and refund the wallet?')">Reject</button>
                </form>@endif</td></tr>
    @empty <tr><td colspan="6" class="muted">Nothing here.</td></tr> @endforelse
</table></div>@include('partials.pagination', ['paginator' => $payouts])</div>
@endsection
