@extends('layouts.app')
@section('title', 'Royalty import')
@section('content')
<div class="top"><div><h1>Royalty import</h1><p>Upload the monthly sales report from your distribution partner to credit artists’ wallets in Naira.</p></div></div>
<div class="grid g-main">
    <div class="card"><h2>Past imports</h2>
        @if ($imports->isEmpty())<p class="muted small mt">No imports yet.</p>@else
        <div class="table-wrap mt"><table>
            <tr><th>Period</th><th>File</th><th>Rows</th><th>Unmatched</th><th class="right">USD</th><th>Rate</th><th class="right">Credited</th></tr>
            @foreach ($imports as $i)<tr><td>{{ $i->period }}</td><td class="small">{{ $i->file_name }}<br><span class="muted">{{ $i->source }}</span></td><td>{{ number_format($i->rows) }}</td>
                <td>{{ $i->unmatched_rows ? number_format($i->unmatched_rows) : '0' }}</td><td class="right">${{ number_format($i->total_usd, 2) }}</td><td>₦{{ rtrim(rtrim(number_format($i->fx_rate, 4), '0'), '.') }}</td>
                <td class="right"><b>{{ naira($i->credited_kobo) }}</b></td></tr>@endforeach
        </table></div>@include('partials.pagination', ['paginator' => $imports])@endif
    </div>
    <form class="card" method="post" enctype="multipart/form-data" action="{{ route('admin.royalties.store') }}" style="align-self:start" data-confirm="Import this report and credit wallets? This can’t be undone automatically.">@csrf
        <h2>New import</h2>
        <p class="muted small mb">CSV with columns <code>isrc</code> and <code>amount_usd</code> (or revenue/earnings). Optional: <code>store</code>, <code>country</code>, <code>units</code>. <a href="{{ route('admin.royalties.template') }}">Download a template</a>.</p>
        <label>Report file (.csv)</label><input type="file" name="file" accept=".csv,text/csv" required>
        <label class="mt">Sales period</label><input type="month" name="period" value="{{ now()->subMonths(2)->format('Y-m') }}" required>
        <label class="mt">USD → NGN rate</label><input type="number" step="0.0001" name="fx_rate" value="{{ $fx }}" required>
        <label class="mt">Source <span class="hint">(optional)</span></label><input type="text" name="source" placeholder="e.g. Partner statement Aug 2026">
        <p class="help">Each artist is credited the share of their current plan (e.g. 85% on Starter, 100% on Artist).</p>
        <button class="btn btn-green btn-block mt">Import &amp; credit wallets</button>
    </form>
</div>
@endsection
