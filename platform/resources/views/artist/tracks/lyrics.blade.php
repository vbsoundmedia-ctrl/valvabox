@extends('layouts.app')
@section('title', 'Lyrics · '.$track->title)
@section('content')
<div class="top"><div><h1>Lyrics: {{ $track->title }}</h1><p>{{ $release->primary_artist }} · {{ $release->title }}</p></div>
    <a class="btn btn-light" href="{{ route('releases.show', $release) }}">← Back to release</a></div>

<form method="post" action="{{ route('tracks.lyrics.save', $track) }}" id="lyricsForm">@csrf @method('PUT')
<div class="grid g2">
    <div class="card">
        <h2>1. Plain lyrics</h2>
        <p class="muted small mb">One line per sung line. Leave a blank line between verses. Don’t add titles like “[Chorus]”, artist names or repeat markers like “x2”. Write every line out.</p>
        <textarea name="lyrics" id="plain" style="min-height:440px;font-size:14px">{{ old('lyrics', $track->lyrics) }}</textarea>
    </div>
    <div class="card">
        <h2>2. Sync to the music <span class="badge muted">optional</span></h2>
        <p class="muted small mb">Press <b>Start sync</b>, play the song, and tap <b>Stamp line</b> (or the space bar) as each line starts. This creates time-synced lyrics (LRC) for Apple Music, Spotify and Instagram.</p>
        @if ($track->audio_path)
            <audio id="player" controls preload="metadata" src="{{ route('files.audio', $track) }}"></audio>
            <div class="row mt">
                <button type="button" class="btn btn-dark btn-sm" id="start">Start sync</button>
                <button type="button" class="btn btn-green btn-sm" id="stamp" disabled>Stamp line ⎵</button>
                <button type="button" class="btn btn-light btn-sm" id="undo" disabled>Undo</button>
            </div>
            <div class="sync-lines mt" id="lines"></div>
        @else
            <div class="alert alert-warn small">Upload the audio for this track first to use the sync tool.</div>
        @endif
        <label class="mt">LRC result <span class="hint">(you can also paste an .lrc file here)</span></label>
        <textarea name="lyrics_lrc" id="lrc" style="min-height:140px;font-family:ui-monospace,monospace;font-size:12.5px">{{ old('lyrics_lrc', $track->lyrics_lrc) }}</textarea>
    </div>
</div>
<div class="row mt"><button class="btn btn-green" type="submit">Save lyrics</button></div>
</form>

<script>
(function () {
  var player = document.getElementById('player'); if (!player) return;
  var plain = document.getElementById('plain'), lrc = document.getElementById('lrc'), box = document.getElementById('lines');
  var startBtn = document.getElementById('start'), stampBtn = document.getElementById('stamp'), undoBtn = document.getElementById('undo');
  var lines = [], times = [], idx = 0;
  function fmt(t) { var m = Math.floor(t / 60), s = (t - m * 60).toFixed(2); return '[' + String(m).padStart(2, '0') + ':' + s.padStart(5, '0') + ']'; }
  function render() {
    box.innerHTML = '';
    lines.forEach(function (l, i) {
      var d = document.createElement('div'); if (i === idx) d.className = 'cur';
      d.innerHTML = '<span class="t">' + (times[i] != null ? fmt(times[i]) : '··:··.··') + '</span><span></span>';
      d.lastChild.textContent = l; d.onclick = function () { if (times[i] != null) player.currentTime = times[i]; };
      box.appendChild(d);
    });
    var cur = box.querySelector('.cur'); if (cur) cur.scrollIntoView({block: 'nearest'});
    lrc.value = lines.map(function (l, i) { return times[i] != null ? fmt(times[i]) + l : null; }).filter(Boolean).join('\n');
  }
  startBtn.onclick = function () {
    lines = plain.value.split('\n').map(function (l) { return l.trim(); }).filter(Boolean);
    if (!lines.length) { alert('Type or paste the plain lyrics first.'); return; }
    times = []; idx = 0; stampBtn.disabled = false; undoBtn.disabled = false;
    player.currentTime = 0; player.play(); render(); stampBtn.focus();
  };
  function stamp() { if (idx >= lines.length) return; times[idx] = player.currentTime; idx++; render(); if (idx >= lines.length) { stampBtn.disabled = true; } }
  stampBtn.onclick = stamp;
  undoBtn.onclick = function () { if (idx > 0) { idx--; times[idx] = null; stampBtn.disabled = false; render(); } };
  document.addEventListener('keydown', function (e) {
    if (e.code === 'Space' && !stampBtn.disabled && document.activeElement.tagName !== 'TEXTAREA' && document.activeElement.tagName !== 'INPUT') { e.preventDefault(); stamp(); }
  });
})();
</script>
@endsection
