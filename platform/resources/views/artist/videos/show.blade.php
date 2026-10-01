@extends('layouts.app')
@section('title', $video->title)
@section('content')
<div class="top">
    <div><span class="badge {{ status_class($video->status) }}">{{ $video->statusLabel() }}</span><h1 style="margin-top:8px">{{ $video->title }}</h1><p>{{ $video->artist }} · {{ $video->release_date?->format('j M Y') }}@if ($video->isrc) · ISRC {{ $video->isrc }}@endif</p></div>
    @if ($video->isEditable())<div class="row"><a class="btn btn-light" href="{{ route('videos.edit', $video) }}">Edit</a>
        @unless ($video->paid_at)<form method="post" action="{{ route('videos.destroy', $video) }}" data-confirm="Delete this video?">@csrf @method('DELETE')<button class="btn btn-danger">Delete</button></form>@endunless</div>@endif
</div>
@if ($video->status === 'rejected' && $video->rejection_reason)<div class="alert alert-bad"><b>Changes needed:</b> {{ $video->rejection_reason }}</div>@endif
@if ($video->youtube_url)<div class="alert alert-ok">Live on YouTube: <a href="{{ $video->youtube_url }}" target="_blank">{{ $video->youtube_url }}</a></div>@endif
<div class="grid g-main">
    <div class="card">
        @if ($video->thumbnail_path)<img src="{{ route('files.thumbnail', $video) }}" alt="" style="width:100%;border-radius:12px">@endif
        <dl class="meta mt">
            <dt>Video</dt><dd>@if ($video->video_path){{ $video->video_name }}@elseif ($video->video_url)<a href="{{ $video->video_url }}" target="_blank">Download link</a>@endif</dd>
            <dt>Linked track</dt><dd>{{ $video->track?->title ?? '—' }}</dd>
            <dt>Director</dt><dd>{{ $video->director ?: '—' }}</dd>
            <dt>Explicit</dt><dd>{{ $video->explicit ? 'Yes' : 'No' }}</dd>
        </dl>
    </div>
    @if ($video->isEditable())
        <form class="card" method="post" action="{{ route('videos.submit', $video) }}">@csrf
            <h2>Submit video</h2>
            <label class="check mt"><input type="checkbox" name="confirm_rights" value="1" required> I own or control all rights to this video and the music in it.</label>
            <div class="between mt"><div><div class="muted small">To pay</div><div class="display" style="font-size:24px;font-weight:700">{{ $fee ? naira($fee) : 'Free' }}</div>
                @if (! $fee && ! $video->paid_at)<div class="tiny muted">Included in your plan</div>@endif</div>
                <button class="btn btn-green" type="submit">{{ $fee ? 'Pay & submit' : 'Submit' }}</button></div>
        </form>
    @endif
</div>
@endsection
