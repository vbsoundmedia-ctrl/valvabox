@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="top">
    <div><h1>Welcome, {{ $user->displayName() }} 👋</h1><p>Here’s how your music is doing.</p></div>
    <a class="btn btn-green" href="{{ route('releases.create') }}">+ New release</a>
</div>

<div class="grid g4">
    <div class="card kpi dark"><small>Wallet balance</small><div class="num">{{ naira($balance, true) }}</div>
        <a class="btn btn-green btn-sm mt" href="{{ route('wallet') }}">Withdraw</a></div>
    <div class="card kpi"><small>Streams &amp; plays reported</small><div class="num">{{ number_format($totalUnits) }}</div><span class="muted small">All time</span></div>
    <div class="card kpi"><small>Live releases</small><div class="num">{{ $releaseCounts['live'] ?? 0 }}</div><span class="muted small">{{ ($releaseCounts['in_review'] ?? 0) + ($releaseCounts['approved'] ?? 0) + ($releaseCounts['delivered'] ?? 0) }} on the way</span></div>
    <div class="card kpi"><small>Music videos</small><div class="num">{{ $videoCount }}</div><a class="small" href="{{ route('videos.create') }}">Add a video →</a></div>
</div>

<div class="grid g-main mt">
    <div class="card">
        <h2>Earnings by month</h2><p class="muted small">Royalties credited to your wallet in Naira</p>
        @php($max = max(1, $months->max()))
        <div class="chart">
            @foreach ($months as $m => $v)<div class="bar {{ $loop->last ? '' : 'dim' }}" style="height:{{ max(2, round($v / $max * 100)) }}%" title="{{ $m }}: {{ naira($v) }}"></div>@endforeach
        </div>
        <div class="chart-labels">@foreach ($months as $m => $v)<span>{{ $m }}</span>@endforeach</div>
        @if ($months->sum() === 0)<p class="muted small mt">Earnings show up here once stores report your first streams (usually 2–3 months after release).</p>@endif
    </div>
    <div class="card">
        <h2>Top stores</h2><p class="muted small">By earnings, all time</p>
        <div class="mt">
            @forelse ($topStores as $s)
                <div class="list-row"><span>{{ $s->store }}</span><b>{{ naira($s->total) }}</b></div>
            @empty
                <p class="muted small">No store reports yet.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="card mt">
    <div class="card-head"><h2>Recent releases</h2><a class="small" href="{{ route('releases.index') }}">View all →</a></div>
    @if ($recent->isEmpty())
        <div class="empty"><div class="big">🎶</div><p>You haven’t created a release yet.</p><a class="btn btn-dark mt" href="{{ route('releases.create') }}">Create your first release</a></div>
    @else
        <div class="table-wrap"><table>
            <tr><th>Release</th><th>Type</th><th>Release date</th><th>Status</th></tr>
            @foreach ($recent as $r)
                <tr>
                    <td><a href="{{ route('releases.show', $r) }}" class="row" style="text-decoration:none;color:inherit;flex-wrap:nowrap">
                        @if ($r->artwork_path)<img class="cover" src="{{ route('files.artwork', $r) }}" alt="">@else<span class="cover"></span>@endif
                        <span><b>{{ $r->title }}</b><br><span class="muted small">{{ $r->primary_artist }}</span></span></a></td>
                    <td>{{ $r->typeLabel() }}</td>
                    <td class="nowrap">{{ $r->release_date?->format('j M Y') ?? '—' }}</td>
                    <td><span class="badge {{ status_class($r->status) }}">{{ $r->statusLabel() }}</span></td>
                </tr>
            @endforeach
        </table></div>
    @endif
</div>
@endsection
