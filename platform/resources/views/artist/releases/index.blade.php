@extends('layouts.app')
@section('title', 'Music releases')
@section('content')
<div class="top"><div><h1>Music releases</h1><p>Singles, EPs and albums.</p></div><a class="btn btn-green" href="{{ route('releases.create') }}">+ New release</a></div>
<div class="card">
    @if ($releases->isEmpty())
        <div class="empty"><div class="big">🎶</div><p>No releases yet.</p><a class="btn btn-dark mt" href="{{ route('releases.create') }}">Create your first release</a></div>
    @else
        <div class="table-wrap"><table>
            <tr><th>Release</th><th>Type</th><th>Tracks</th><th>Release date</th><th>UPC</th><th>Status</th></tr>
            @foreach ($releases as $r)
                <tr>
                    <td><a href="{{ route('releases.show', $r) }}" class="row" style="text-decoration:none;color:inherit;flex-wrap:nowrap">
                        @if ($r->artwork_path)<img class="cover" src="{{ route('files.artwork', $r) }}" alt="">@else<span class="cover"></span>@endif
                        <span><b>{{ $r->title }}</b><br><span class="muted small">{{ $r->primary_artist }}</span></span></a></td>
                    <td>{{ $r->typeLabel() }}</td><td>{{ $r->tracks_count }}</td>
                    <td class="nowrap">{{ $r->release_date?->format('j M Y') ?? '—' }}</td>
                    <td class="small">{{ $r->upc ?? '—' }}</td>
                    <td><span class="badge {{ status_class($r->status) }}">{{ $r->statusLabel() }}</span></td>
                </tr>
            @endforeach
        </table></div>
        @include('partials.pagination', ['paginator' => $releases])
    @endif
</div>
@endsection
