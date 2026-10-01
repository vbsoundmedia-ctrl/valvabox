@extends('layouts.app')
@section('title', 'Releases')
@section('content')
<div class="top"><div><h1>Releases</h1><p>Review, approve and deliver.</p></div>
    <form method="get" class="row"><input type="hidden" name="status" value="{{ $status }}"><input type="text" name="q" value="{{ request('q') }}" placeholder="Title, artist or UPC" style="width:220px"><button class="btn btn-light">Search</button></form></div>
<div class="tabs">
    @foreach (['in_review' => 'In review', 'approved' => 'Approved', 'delivered' => 'Delivered', 'live' => 'Live', 'rejected' => 'Needs changes', 'pending_payment' => 'Awaiting payment', 'draft' => 'Drafts', 'takedown' => 'Taken down', 'all' => 'All'] as $k => $v)
        <a class="{{ $status === $k ? 'on' : '' }}" href="{{ route('admin.releases.index', ['status' => $k]) }}">{{ $v }}@if ($k !== 'all') ({{ $counts[$k] ?? 0 }})@endif</a>
    @endforeach
</div>
<div class="card">
    @if ($releases->isEmpty())<div class="empty">Nothing here.</div>@else
    <div class="table-wrap"><table>
        <tr><th>Release</th><th>Artist account</th><th>Type</th><th>Tracks</th><th>Release date</th><th>Submitted</th><th>Status</th></tr>
        @foreach ($releases as $r)
            <tr><td><a href="{{ route('admin.releases.show', $r) }}" class="row" style="flex-wrap:nowrap;text-decoration:none;color:inherit">
                    @if ($r->artwork_path)<img class="cover" src="{{ route('files.artwork', $r) }}" alt="">@else<span class="cover"></span>@endif
                    <span><b>{{ $r->title }}</b><br><span class="small muted">{{ $r->primary_artist }}</span></span></a></td>
                <td class="small">{{ $r->user->email }}</td><td>{{ $r->typeLabel() }}</td><td>{{ $r->tracks_count }}</td>
                <td class="nowrap">{{ $r->release_date?->format('j M Y') }}</td><td class="small nowrap">{{ $r->submitted_at?->format('j M, H:i') ?? '—' }}</td>
                <td><span class="badge {{ status_class($r->status) }}">{{ $r->statusLabel() }}</span></td></tr>
        @endforeach
    </table></div>
    @include('partials.pagination', ['paginator' => $releases])
    @endif
</div>
@endsection
