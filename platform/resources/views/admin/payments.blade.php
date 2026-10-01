@extends('layouts.app')
@section('title', 'Payments')
@section('content')
<div class="top"><div><h1>Payments</h1><p>Money received from artists.</p></div></div>
<div class="tabs">@foreach (['' => 'All', 'success' => 'Successful', 'pending' => 'Pending', 'failed' => 'Failed'] as $k => $v)<a class="{{ request('status', '') === $k ? 'on' : '' }}" href="?status={{ $k }}">{{ $v }}</a>@endforeach</div>
<div class="card"><div class="table-wrap"><table>
    <tr><th>Date</th><th>Artist</th><th>For</th><th>Reference</th><th>Gateway</th><th class="right">Amount</th><th>Status</th></tr>
    @forelse ($payments as $p)
        <tr><td class="small nowrap">{{ $p->created_at->format('j M Y H:i') }}</td><td class="small"><a href="{{ route('admin.users.show', $p->user) }}">{{ $p->user->email }}</a></td>
            <td>{{ $p->description }}</td><td class="small muted">{{ $p->reference }}</td><td>{{ $p->gateway }}</td><td class="right"><b>{{ naira($p->amount_kobo) }}</b></td>
            <td><span class="badge {{ status_class($p->status) }}">{{ $p->status }}</span></td></tr>
    @empty <tr><td colspan="7" class="muted">No payments.</td></tr> @endforelse
</table></div>@include('partials.pagination', ['paginator' => $payments])</div>
@endsection
