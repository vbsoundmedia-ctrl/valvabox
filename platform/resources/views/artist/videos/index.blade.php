@extends('layouts.app')
@section('title', 'Music videos')
@section('content')
<div class="top"><div><h1>Music videos</h1><p>Send your official videos to YouTube, Apple Music, TIDAL and more.</p></div><a class="btn btn-green" href="{{ route('videos.create') }}">+ New video</a></div>
<div class="card">
    @if ($videos->isEmpty())
        <div class="empty"><div class="big">🎬</div><p>No videos yet.</p><a class="btn btn-dark mt" href="{{ route('videos.create') }}">Add a music video</a></div>
    @else
        <div class="table-wrap"><table>
            <tr><th>Video</th><th>Release date</th><th>ISRC</th><th>Status</th></tr>
            @foreach ($videos as $v)
                <tr><td><a href="{{ route('videos.show', $v) }}"><b>{{ $v->title }}</b></a><br><span class="muted small">{{ $v->artist }}</span></td>
                    <td>{{ $v->release_date?->format('j M Y') }}</td><td class="small">{{ $v->isrc ?? '—' }}</td>
                    <td><span class="badge {{ status_class($v->status) }}">{{ $v->statusLabel() }}</span></td></tr>
            @endforeach
        </table></div>
        @include('partials.pagination', ['paginator' => $videos])
    @endif
</div>
@endsection
