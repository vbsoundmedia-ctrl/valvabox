@extends('layouts.app')
@section('title', 'Review · '.$release->title)
@section('content')
<div class="top">
    <div class="row" style="flex-wrap:nowrap;align-items:flex-start">
        @if ($release->artwork_path)<a href="{{ route('files.artwork', [$release, 'full']) }}" target="_blank"><img class="cover lg" src="{{ route('files.artwork', $release) }}" alt=""></a>@else<span class="cover lg"></span>@endif
        <div><span class="badge {{ status_class($release->status) }}">{{ $release->statusLabel() }}</span>
            <h1 style="margin-top:8px">{{ $release->title }}</h1>
            <p>{{ $release->primary_artist }}@if ($release->featured_artists) feat. {{ $release->featured_artists }}@endif</p>
            <p class="small">Account: <a href="{{ route('admin.users.show', $release->user) }}">{{ $release->user->name }} · {{ $release->user->email }}</a> · {{ $release->user->currentPlan()->name }} plan</p>
            <a class="btn btn-dark btn-sm mt" href="{{ route('admin.releases.package', $release) }}">⇩ Download delivery pack (ZIP)</a>
        </div>
    </div>
    <a class="btn btn-light" href="{{ route('admin.releases.index') }}">← Queue</a>
</div>

<div class="grid g-main">
    <div>
        <div class="card">
            <h2>Metadata</h2>
            <dl class="meta mt">
                <dt>Type</dt><dd>{{ $release->typeLabel() }}</dd>
                <dt>Genre / language</dt><dd>{{ $release->genre }} · {{ $release->language }}</dd>
                <dt>Release date</dt><dd>{{ $release->release_date?->format('l j F Y') }}</dd>
                <dt>Label</dt><dd>{{ $release->label_name ?: $release->primary_artist }}</dd>
                <dt>© / ℗</dt><dd>© {{ $release->copyright_line }} · ℗ {{ $release->phonographic_line }}</dd>
                <dt>Explicit</dt><dd>{{ $release->explicit ? 'Yes' : 'No' }}</dd>
                <dt>Paid</dt><dd>{{ $release->paid_at ? $release->paid_at->format('j M Y H:i') : 'Covered by plan / not paid' }}</dd>
                <dt>Stores</dt><dd>{{ $release->stores->pluck('name')->join(', ') ?: '—' }}</dd>
                @if ($release->notes)<dt>Artist notes</dt><dd>{{ $release->notes }}</dd>@endif
            </dl>
        </div>
        <div class="card mt">
            <h2>Tracks</h2>
            @foreach ($release->tracks as $t)
                <div style="border-top:1px solid var(--line);padding:14px 0">
                    <div class="between"><b>{{ $t->position }}. {{ $t->title }}@if ($t->version) ({{ $t->version }})@endif</b>
                        @if ($t->audio_path)<a class="small" href="{{ route('files.audio', [$t, 'download' => 1]) }}">⇩ {{ $t->audio_name }} ({{ number_format($t->audio_size / 1048576, 1) }} MB)</a>@endif</div>
                    <div class="small muted">Composers: {{ $t->composers }}@if ($t->lyricists) · Lyrics: {{ $t->lyricists }}@endif @if ($t->producers) · Prod: {{ $t->producers }}@endif @if ($t->featured_artists) · Feat: {{ $t->featured_artists }}@endif · {{ $t->explicit ? 'Explicit' : 'Clean' }}</div>
                    @if ($t->audio_path)<audio controls preload="none" src="{{ route('files.audio', $t) }}" class="mt" style="margin-top:8px"></audio>@endif
                    @if ($t->hasLyrics())<details class="mt" style="margin-top:6px"><summary class="small">Lyrics{{ trim((string) $t->lyrics_lrc) !== '' ? ' (synced)' : '' }}</summary><pre class="code">{{ $t->lyrics }}</pre></details>@endif
                </div>
            @endforeach
        </div>
    </div>

    <form class="card" method="post" action="{{ route('admin.releases.update', $release) }}" style="align-self:start">@csrf @method('PUT')
        <h2>Decision</h2>
        <p class="muted small mb">Assign codes, then approve. After you upload the pack to your distribution partner, mark it <b>Delivered</b>, then <b>Live</b> with store links.</p>
        <label>UPC / EAN</label><input type="text" name="upc" value="{{ old('upc', $release->upc) }}" placeholder="12–14 digits">
        @foreach ($release->tracks as $t)
            <label class="mt">ISRC: {{ $t->position }}. {{ \Illuminate\Support\Str::limit($t->title, 30) }}</label>
            <input type="text" name="isrc[{{ $t->id }}]" value="{{ old('isrc.'.$t->id, $t->isrc) }}" placeholder="NGXXX2600001" style="text-transform:uppercase">
        @endforeach
        <label class="mt">Status</label>
        <select name="status" id="st">@foreach (\App\Models\Release::STATUSES as $k => $v)<option value="{{ $k }}" @selected(old('status', $release->status) === $k)>{{ $v }}</option>@endforeach</select>
        <label class="mt">Reason <span class="hint">(shown to the artist if “Needs changes”)</span></label>
        <textarea name="rejection_reason" style="min-height:90px" placeholder="e.g. Artwork contains a website URL. Please upload clean artwork.">{{ old('rejection_reason', $release->rejection_reason) }}</textarea>
        <details class="mt"><summary class="small">Store links (for “Live”)</summary>
            @foreach ($release->stores as $s)<label class="mt small">{{ $s->name }}</label><input type="url" name="store_url[{{ $s->id }}]" value="{{ $s->pivot->url }}" placeholder="https://">@endforeach
        </details>
        <button class="btn btn-green btn-block mt" type="submit">Save decision</button>
    </form>
</div>
@endsection
