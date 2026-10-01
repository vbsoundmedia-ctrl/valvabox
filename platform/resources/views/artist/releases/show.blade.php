@extends('layouts.app')
@section('title', $release->title)
@section('content')
<div class="top">
    <div class="row" style="flex-wrap:nowrap;align-items:flex-start">
        @if ($release->artwork_path)<img class="cover lg" style="width:120px;height:120px" src="{{ route('files.artwork', $release) }}" alt="">@else<span class="cover lg" style="width:120px;height:120px"></span>@endif
        <div>
            <span class="badge {{ status_class($release->status) }}">{{ $release->statusLabel() }}</span>
            <h1 class="mt" style="margin-top:8px">{{ $release->title }}</h1>
            <p>{{ $release->primary_artist }}@if ($release->featured_artists) feat. {{ $release->featured_artists }}@endif · {{ $release->typeLabel() }} · {{ $release->genre }} · {{ $release->release_date?->format('j M Y') }}</p>
            @if ($release->upc)<p class="small">UPC {{ $release->upc }}</p>@endif
        </div>
    </div>
    @if ($release->isEditable())
        <div class="row"><a class="btn btn-light" href="{{ route('releases.edit', $release) }}">Edit details</a>
            @unless ($release->paid_at)
                <form method="post" action="{{ route('releases.destroy', $release) }}" data-confirm="Delete this release and its files?">@csrf @method('DELETE')<button class="btn btn-danger" type="submit">Delete</button></form>
            @endunless
        </div>
    @endif
</div>

@if ($release->status === 'rejected' && $release->rejection_reason)
    <div class="alert alert-bad"><b>Changes needed:</b> {{ $release->rejection_reason }}<br><span class="small">Fix the issues below and submit again. No extra payment needed.</span></div>
@elseif ($release->status === 'in_review')
    <div class="alert alert-info">Your release is with our review team (usually 1–2 working days). We’ll update the status here.</div>
@elseif (in_array($release->status, ['approved', 'delivered']))
    <div class="alert alert-info">Approved and on its way to stores. Most stores show it on your release date; some take up to 2 weeks.</div>
@endif

<div class="grid g-main">
    <div class="card">
        <div class="card-head"><div><h2>Tracks</h2><p class="muted small">WAV or FLAC, with songwriter credits and lyrics.</p></div>
            @if ($release->isEditable())<a class="btn btn-dark btn-sm" href="{{ route('tracks.create', $release) }}">+ Add track</a>@endif</div>
        @forelse ($release->tracks as $t)
            <div class="list-row">
                <div><b>{{ $t->position }}. {{ $t->title }}</b>@if ($t->version) <span class="muted">({{ $t->version }})</span>@endif
                    @if ($t->explicit)<span class="chip">E</span>@endif
                    <div class="small muted">{{ $t->audio_name ?: 'No audio yet' }}@if ($t->isrc) · ISRC {{ $t->isrc }}@endif</div>
                    <div class="mt" style="margin-top:6px">
                        <span class="chip">{{ $t->audio_path ? '✓ Audio' : '✗ Audio' }}</span>
                        <span class="chip">{{ $t->hasLyrics() ? '✓ Lyrics' : '– Lyrics' }}</span>
                        <span class="chip">{{ trim((string) $t->lyrics_lrc) !== '' ? '✓ Synced' : '– Synced' }}</span>
                    </div>
                </div>
                <div class="row" style="flex-wrap:nowrap">
                    <a class="btn btn-light btn-sm" href="{{ route('tracks.lyrics', $t) }}">Lyrics</a>
                    @if ($release->isEditable())
                        <a class="btn btn-light btn-sm" href="{{ route('tracks.edit', $t) }}">Edit</a>
                        <form method="post" action="{{ route('tracks.destroy', $t) }}" data-confirm="Remove this track?">@csrf @method('DELETE')<button class="btn btn-danger btn-sm" type="submit">✕</button></form>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty"><p>No tracks yet.</p>@if ($release->isEditable())<a class="btn btn-dark mt" href="{{ route('tracks.create', $release) }}">Add your first track</a>@endif</div>
        @endforelse
    </div>

    <div>
        <form class="card" method="post" action="{{ route('releases.stores', $release) }}">@csrf
            <div class="card-head"><div><h2>Stores</h2><p class="muted small">Where should this release go?</p></div></div>
            @php($chosen = $release->stores->pluck('id')->all())
            <div class="grid" style="gap:8px">
                @foreach ($stores as $s)
                    <label class="check" style="font-weight:500"><input type="checkbox" name="stores[]" value="{{ $s->id }}" @checked(in_array($s->id, $chosen)) @disabled(! $release->isEditable())> {{ $s->name }}
                        @if ($url = $release->stores->firstWhere('id', $s->id)?->pivot->url) · <a href="{{ $url }}" target="_blank">Listen</a>@endif</label>
                @endforeach
            </div>
            @if ($release->isEditable())<button class="btn btn-light btn-sm mt" type="submit">Save stores</button>@endif
        </form>

        @if ($release->isEditable())
            <form class="card mt" method="post" action="{{ route('releases.submit', $release) }}">@csrf
                <h2>Submit for distribution</h2>
                @if ($problems)
                    <div class="alert alert-warn small mt"><b>Before you can submit:</b><ul>@foreach ($problems as $p)<li>{{ $p }}</li>@endforeach</ul></div>
                @else
                    <p class="muted small mt">Everything looks ready.</p>
                @endif
                <label class="check mt"><input type="checkbox" name="confirm_rights" value="1" required> I own or control all rights to this music, artwork and lyrics.</label>
                <div class="between mt">
                    <div><div class="muted small">To pay</div><div class="display" style="font-size:24px;font-weight:700">{{ $fee ? naira($fee) : 'Free' }}</div>
                        @if (! $fee && ! $release->paid_at)<div class="tiny muted">Covered by your {{ auth()->user()->currentPlan()->name }} plan</div>@endif</div>
                    <button class="btn btn-green" type="submit" @disabled($problems)>{{ $fee ? 'Pay & submit' : 'Submit' }}</button>
                </div>
                @if ($fee && ! auth()->user()->hasActivePlan())<p class="tiny muted mt">Releasing often? <a href="{{ route('plans') }}">A yearly plan</a> covers unlimited releases.</p>@endif
            </form>
        @endif
    </div>
</div>
@endsection
