<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Models\Track;
use App\Services\Uploads;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrackController extends Controller
{
    private function rules(bool $audioRequired): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'version' => ['nullable', 'string', 'max:100'],
            'featured_artists' => ['nullable', 'string', 'max:255'],
            'composers' => ['required', 'string', 'max:255'],
            'lyricists' => ['nullable', 'string', 'max:255'],
            'producers' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', Rule::in(ReleaseController::LANGUAGES)],
            'explicit' => ['boolean'],
            'audio' => [$audioRequired ? 'required' : 'nullable', 'file', 'extensions:wav,flac', 'max:512000'],
        ];
    }

    private array $messages = [
        'audio.extensions' => 'Audio must be a WAV or FLAC file (16 or 24-bit, 44.1 kHz or higher). MP3 is not accepted by stores.',
        'audio.uploaded' => 'The audio upload failed — the file is probably bigger than your server allows. Ask your host to raise upload_max_filesize.',
        'composers.required' => 'Enter the songwriter(s)/composer(s) — stores require real names.',
    ];

    public function create(Release $release)
    {
        $this->authorizeOwner($release);
        abort_unless($release->isEditable(), 403);

        return view('artist.tracks.form', ['release' => $release, 'track' => new Track([
            'language' => $release->language, 'explicit' => $release->explicit, 'title' => $release->tracks()->count() ? '' : $release->title,
        ])]);
    }

    public function store(Request $request, Release $release, Uploads $uploads)
    {
        $this->authorizeOwner($release);
        abort_unless($release->isEditable(), 403);
        $data = $request->validate($this->rules(true), $this->messages);
        $data['explicit'] = $request->boolean('explicit');
        unset($data['audio']);

        $track = new Track($data);
        $track->release_id = $release->id;
        $track->position = ((int) $release->tracks()->max('position')) + 1;
        $this->saveAudio($request, $track, $uploads);
        $track->save();

        return redirect()->route('releases.show', $release)->with('success', "Track “{$track->title}” added.");
    }

    public function edit(Track $track)
    {
        $this->authorizeOwner($track->release);
        abort_unless($track->release->isEditable(), 403);

        return view('artist.tracks.form', ['release' => $track->release, 'track' => $track]);
    }

    public function update(Request $request, Track $track, Uploads $uploads)
    {
        $release = $track->release;
        $this->authorizeOwner($release);
        abort_unless($release->isEditable(), 403);
        $data = $request->validate($this->rules(! $track->audio_path), $this->messages);
        $data['explicit'] = $request->boolean('explicit');
        unset($data['audio']);
        $track->fill($data);
        $this->saveAudio($request, $track, $uploads);
        $track->save();

        return redirect()->route('releases.show', $release)->with('success', 'Track updated.');
    }

    public function destroy(Track $track, Uploads $uploads)
    {
        $release = $track->release;
        $this->authorizeOwner($release);
        abort_unless($release->isEditable(), 403);
        $uploads->delete($track->audio_path);
        $track->delete();
        $release->tracks()->get()->values()->each(fn ($t, $i) => $t->forceFill(['position' => $i + 1])->save());

        return back()->with('success', 'Track removed.');
    }

    public function lyrics(Track $track)
    {
        $this->authorizeOwner($track->release);

        return view('artist.tracks.lyrics', ['track' => $track, 'release' => $track->release]);
    }

    public function saveLyrics(Request $request, Track $track)
    {
        $this->authorizeOwner($track->release);
        $data = $request->validate(['lyrics' => ['nullable', 'string', 'max:20000'], 'lyrics_lrc' => ['nullable', 'string', 'max:40000']]);
        $track->forceFill($data)->save();

        return back()->with('success', 'Lyrics saved.');
    }

    private function saveAudio(Request $request, Track $track, Uploads $uploads): void
    {
        if (! $request->hasFile('audio')) {
            return;
        }
        $file = $request->file('audio');
        $uploads->delete($track->audio_path);
        $track->audio_path = $uploads->store($file, "releases/{$track->release_id}/audio");
        $track->audio_name = mb_substr($file->getClientOriginalName(), 0, 250);
        $track->audio_size = $file->getSize();
    }
}
