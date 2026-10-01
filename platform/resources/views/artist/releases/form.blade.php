@extends('layouts.app')
@section('title', $release->exists ? 'Edit release' : 'New release')
@section('content')
<div class="top"><div><h1>{{ $release->exists ? 'Edit release' : 'New release' }}</h1><p>Step 1 of 3: release details and artwork.</p></div></div>
<div class="steps"><span class="done">1 · Details &amp; artwork</span><span>2 · Tracks &amp; lyrics</span><span>3 · Stores &amp; submit</span></div>
<form class="card" method="post" enctype="multipart/form-data" action="{{ $release->exists ? route('releases.update', $release) : route('releases.store') }}">
    @csrf @if ($release->exists) @method('PUT') @endif
    <div class="form-grid">
        <div><label>Release type</label>
            <select name="type">@foreach (\App\Models\Release::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('type', $release->type) === $k)>{{ $v }}</option>@endforeach</select>
            <div class="help">Single: 1–3 tracks · EP: 4–6 · Album: 7+</div></div>
        <div><label>Release title</label><input type="text" name="title" value="{{ old('title', $release->title) }}" required></div>
        <div><label>Primary artist</label><input type="text" name="primary_artist" value="{{ old('primary_artist', $release->primary_artist) }}" required>
            <div class="help">Spell it exactly as on your Spotify/Apple profile.</div></div>
        <div><label>Featured artists <span class="hint">(optional, comma separated)</span></label><input type="text" name="featured_artists" value="{{ old('featured_artists', $release->featured_artists) }}"></div>
        <div><label>Genre</label><select name="genre" required><option value="">Choose…</option>@foreach (\App\Http\Controllers\ReleaseController::GENRES as $g)<option @selected(old('genre', $release->genre) === $g)>{{ $g }}</option>@endforeach</select></div>
        <div><label>Language</label><select name="language">@foreach (\App\Http\Controllers\ReleaseController::LANGUAGES as $l)<option @selected(old('language', $release->language) === $l)>{{ $l }}</option>@endforeach</select></div>
        <div><label>Release date</label><input type="date" name="release_date" value="{{ old('release_date', $release->release_date?->toDateString()) }}" required>
            <div class="help">Give us 2–3 weeks if you want pre-saves and playlist pitching.</div></div>
        <div><label>Label name <span class="hint">(optional)</span></label><input type="text" name="label_name" value="{{ old('label_name', $release->label_name) }}" placeholder="Defaults to your artist name"></div>
        <div><label>© Copyright line</label><input type="text" name="copyright_line" value="{{ old('copyright_line', $release->copyright_line) }}" required><div class="help">Year + owner of the artwork/composition, e.g. “{{ now()->year }} Tobi Music”.</div></div>
        <div><label>℗ Production line</label><input type="text" name="phonographic_line" value="{{ old('phonographic_line', $release->phonographic_line) }}" required><div class="help">Year + owner of the sound recording.</div></div>
        <div class="full"><label>Cover artwork <span class="hint">JPG or PNG, square, at least 3000×3000 px</span></label>
            @if ($release->artwork_path)<div class="row mb"><img class="cover" style="width:90px;height:90px" src="{{ route('files.artwork', $release) }}" alt=""><span class="muted small">Upload a new file only if you want to replace it.</span></div>@endif
            <input type="file" name="artwork" accept="image/jpeg,image/png" {{ $release->artwork_path ? '' : 'required' }}>
            <div class="help">No website URLs, social handles, store logos, prices or blurry images. Stores reject these.</div></div>
        <div class="full"><label class="check"><input type="hidden" name="explicit" value="0"><input type="checkbox" name="explicit" value="1" @checked(old('explicit', $release->explicit))> Contains explicit lyrics</label></div>
        <div class="full"><label>Notes for our team <span class="hint">(optional)</span></label><textarea name="notes" style="min-height:80px">{{ old('notes', $release->notes) }}</textarea></div>
    </div>
    <div class="row mt2"><button class="btn btn-dark" type="submit">{{ $release->exists ? 'Save changes' : 'Save & add tracks →' }}</button>
        <a class="btn btn-light" href="{{ $release->exists ? route('releases.show', $release) : route('releases.index') }}">Cancel</a></div>
</form>
@endsection
