@extends('layouts.app')
@section('title', 'Video · '.$video->title)
@section('content')
<div class="top"><div><span class="badge {{ status_class($video->status) }}">{{ $video->statusLabel() }}</span><h1 style="margin-top:8px">{{ $video->title }}</h1><p>{{ $video->artist }} · <a href="{{ route('admin.users.show', $video->user) }}">{{ $video->user->email }}</a></p></div>
    <a class="btn btn-light" href="{{ route('admin.videos.index') }}">← Videos</a></div>
<div class="grid g-main">
    <div class="card">
        @if ($video->thumbnail_path)<img src="{{ route('files.thumbnail', $video) }}" alt="" style="width:100%;border-radius:12px">@endif
        <dl class="meta mt">
            <dt>Video file</dt><dd>@if ($video->video_path)<a href="{{ route('files.video', [$video, 'download' => 1]) }}">⇩ {{ $video->video_name }}</a>@else—@endif</dd>
            <dt>Download link</dt><dd>@if ($video->video_url)<a href="{{ $video->video_url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($video->video_url, 60) }}</a>@else—@endif</dd>
            <dt>Linked track</dt><dd>{{ $video->track ? $video->track->title.' (ISRC '.($video->track->isrc ?? '—').')' : '—' }}</dd>
            <dt>Director</dt><dd>{{ $video->director ?: '—' }}</dd>
            <dt>Release date</dt><dd>{{ $video->release_date?->format('j M Y') }}</dd>
            <dt>Explicit</dt><dd>{{ $video->explicit ? 'Yes' : 'No' }}</dd>
            <dt>Paid</dt><dd>{{ $video->paid_at?->format('j M Y H:i') ?? 'Covered by plan / not paid' }}</dd>
        </dl>
    </div>
    <form class="card" method="post" action="{{ route('admin.videos.update', $video) }}" style="align-self:start">@csrf @method('PUT')
        <h2>Decision</h2>
        <label class="mt">Video ISRC</label><input type="text" name="isrc" value="{{ old('isrc', $video->isrc) }}">
        <label class="mt">YouTube URL <span class="hint">(when live)</span></label><input type="url" name="youtube_url" value="{{ old('youtube_url', $video->youtube_url) }}">
        <label class="mt">Status</label><select name="status">@foreach (\App\Models\Release::STATUSES as $k => $v)<option value="{{ $k }}" @selected($video->status === $k)>{{ $v }}</option>@endforeach</select>
        <label class="mt">Reason <span class="hint">(if “Needs changes”)</span></label><textarea name="rejection_reason" style="min-height:80px">{{ old('rejection_reason', $video->rejection_reason) }}</textarea>
        <button class="btn btn-green btn-block mt">Save</button>
    </form>
</div>
@endsection
