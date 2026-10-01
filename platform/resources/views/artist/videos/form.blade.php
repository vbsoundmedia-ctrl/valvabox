@extends('layouts.app')
@section('title', $video->exists ? 'Edit video' : 'New video')
@section('content')
<div class="top"><div><h1>{{ $video->exists ? 'Edit video' : 'New music video' }}</h1><p>MP4 or MOV, 1080p or higher, H.264 or ProRes.</p></div></div>
<form class="card" method="post" enctype="multipart/form-data" action="{{ $video->exists ? route('videos.update', $video) : route('videos.store') }}">
    @csrf @if ($video->exists) @method('PUT') @endif
    <div class="form-grid">
        <div><label>Video title</label><input type="text" name="title" value="{{ old('title', $video->title) }}" required></div>
        <div><label>Artist</label><input type="text" name="artist" value="{{ old('artist', $video->artist) }}" required></div>
        <div><label>Linked track <span class="hint">(optional)</span></label>
            <select name="track_id"><option value="">Not on {{ setting('site_name') }}</option>@foreach ($tracks as $t)<option value="{{ $t->id }}" @selected(old('track_id', $video->track_id) == $t->id)>{{ $t->title }} ({{ $t->release->title }})</option>@endforeach</select></div>
        <div><label>Director <span class="hint">(optional)</span></label><input type="text" name="director" value="{{ old('director', $video->director) }}"></div>
        <div><label>Release date</label><input type="date" name="release_date" value="{{ old('release_date', $video->release_date?->toDateString() ?? now()->addDays(14)->toDateString()) }}" required></div>
        <div style="align-self:end"><label class="check"><input type="hidden" name="explicit" value="0"><input type="checkbox" name="explicit" value="1" @checked(old('explicit', $video->explicit))> Explicit content</label></div>
        <div class="full"><label>Video file <span class="hint">max {{ \App\Services\Uploads::maxUploadHuman() }} on this server</span></label>
            @if ($video->video_path)<div class="help mb">Current: {{ $video->video_name }}</div>@endif
            <input type="file" name="video" accept=".mp4,.mov,.m4v,video/mp4,video/quicktime">
            <div class="help"><b>Large file?</b> Upload it to Google Drive, Dropbox or WeTransfer and paste the share link below instead.</div></div>
        <div class="full"><label>…or video download link</label><input type="url" name="video_url" value="{{ old('video_url', $video->video_url) }}" placeholder="https://drive.google.com/…"></div>
        <div class="full"><label>Thumbnail <span class="hint">JPG/PNG, 1920×1080 recommended</span></label>
            @if ($video->thumbnail_path)<img src="{{ route('files.thumbnail', $video) }}" alt="" style="max-width:240px;border-radius:10px" class="mb">@endif
            <input type="file" name="thumbnail" accept="image/jpeg,image/png" {{ $video->thumbnail_path ? '' : 'required' }}></div>
    </div>
    <div class="row mt2"><button class="btn btn-dark" type="submit">Save video</button><a class="btn btn-light" href="{{ route('videos.index') }}">Cancel</a></div>
</form>
@endsection
