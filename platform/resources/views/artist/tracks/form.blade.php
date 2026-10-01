@extends('layouts.app')
@section('title', $track->exists ? 'Edit track' : 'Add track')
@section('content')
<div class="top"><div><h1>{{ $track->exists ? 'Edit track' : 'Add a track' }}</h1><p>{{ $release->title }} · {{ $release->primary_artist }}</p></div></div>
<div class="steps"><span class="done">1 · Details &amp; artwork</span><span class="done">2 · Tracks &amp; lyrics</span><span>3 · Stores &amp; submit</span></div>
<form class="card" method="post" enctype="multipart/form-data" action="{{ $track->exists ? route('tracks.update', $track) : route('tracks.store', $release) }}">
    @csrf @if ($track->exists) @method('PUT') @endif
    <div class="form-grid">
        <div><label>Track title</label><input type="text" name="title" value="{{ old('title', $track->title) }}" required></div>
        <div><label>Version <span class="hint">(optional)</span></label><input type="text" name="version" value="{{ old('version', $track->version) }}" placeholder="e.g. Remix, Acoustic, Sped Up"></div>
        <div><label>Featured artists <span class="hint">(optional)</span></label><input type="text" name="featured_artists" value="{{ old('featured_artists', $track->featured_artists) }}"></div>
        <div><label>Language</label><select name="language">@foreach (\App\Http\Controllers\ReleaseController::LANGUAGES as $l)<option @selected(old('language', $track->language) === $l)>{{ $l }}</option>@endforeach</select></div>
        <div><label>Songwriter(s) / composer(s)</label><input type="text" name="composers" value="{{ old('composers', $track->composers) }}" required placeholder="Full legal names, comma separated"></div>
        <div><label>Lyricist(s) <span class="hint">(optional)</span></label><input type="text" name="lyricists" value="{{ old('lyricists', $track->lyricists) }}"></div>
        <div class="full"><label>Producer(s) <span class="hint">(optional)</span></label><input type="text" name="producers" value="{{ old('producers', $track->producers) }}"></div>
        <div class="full"><label>Audio file <span class="hint">WAV or FLAC · 16/24-bit · 44.1 kHz+ · max {{ \App\Services\Uploads::maxUploadHuman() }} on this server</span></label>
            @if ($track->audio_path)<audio controls preload="none" src="{{ route('files.audio', $track) }}" class="mb"></audio><div class="help mb">Current: {{ $track->audio_name }}. Choose a file only to replace it.</div>@endif
            <input type="file" name="audio" accept=".wav,.flac,audio/wav,audio/flac" {{ $track->audio_path ? '' : 'required' }}></div>
        <div class="full"><label class="check"><input type="hidden" name="explicit" value="0"><input type="checkbox" name="explicit" value="1" @checked(old('explicit', $track->explicit))> Explicit lyrics</label></div>
    </div>
    <div class="row mt2"><button class="btn btn-dark" type="submit">{{ $track->exists ? 'Save track' : 'Upload track' }}</button><a class="btn btn-light" href="{{ route('releases.show', $release) }}">Cancel</a></div>
    @unless ($track->exists)<p class="help mt">You can add lyrics after uploading.</p>@endunless
</form>
@endsection
