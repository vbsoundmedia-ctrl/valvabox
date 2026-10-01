@extends('layouts.app')
@section('title', 'Payments')
@section('content')
<div class="top"><div><h1>Payments</h1><p>Everything you’ve paid {{ setting('site_name') }}.</p></div></div>
<div class="card">
    @if ($payments->isEmpty())
        <div class="empty"><p>No payments yet.</p></div>
    @else
        <div class="table-wrap"><table>
            <tr><th>Date</th><th>For</th><th>Reference</th><th>Via</th><th class="right">Amount</th><th>Status</th><th></th></tr>
            @foreach ($payments as $p)
                <tr><td class="nowrap">{{ $p->created_at->format('j M Y, H:i') }}</td><td>{{ $p->description }}</td><td class="small muted">{{ $p->reference }}</td>
                    <td>{{ ucfirst($p->gateway) }}</td><td class="right nowrap"><b>{{ naira($p->amount_kobo) }}</b></td>
                    <td><span class="badge {{ status_class($p->status) }}">{{ ucfirst($p->status) }}</span></td>
                    <td>@if ($p->status === 'pending')<form method="post" action="{{ route('payments.retry', $p) }}">@csrf<button class="btn btn-light btn-sm">Pay now</button></form>@endif</td></tr>
            @endforeach
        </table></div>
        @include('partials.pagination', ['paginator' => $payments])
    @endif
</div>
@endsection
