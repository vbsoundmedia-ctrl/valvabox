<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use ZipArchive;

class ReleaseController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'in_review');
        $q = Release::with('user')->withCount('tracks');
        if ($status !== 'all') {
            $q->where('status', $status);
        }
        if ($s = $request->query('q')) {
            $q->where(fn ($w) => $w->where('title', 'like', "%{$s}%")->orWhere('primary_artist', 'like', "%{$s}%")->orWhere('upc', $s));
        }

        return view('admin.releases.index', [
            'releases' => $q->orderByRaw('submitted_at is null')->orderBy('submitted_at')->latest('id')->paginate(30)->withQueryString(),
            'status' => $status,
            'counts' => Release::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function show(Release $release)
    {
        $release->load('tracks', 'stores', 'user');

        return view('admin.releases.show', ['release' => $release]);
    }

    /** Save codes (UPC/ISRCs), store links and move the release to a new status. */
    public function update(Request $request, Release $release)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Release::STATUSES))],
            'upc' => ['nullable', 'regex:/^\d{12,14}$/'],
            'rejection_reason' => ['nullable', 'required_if:status,rejected', 'string', 'max:2000'],
            'isrc' => ['array'],
            'isrc.*' => ['nullable', 'regex:/^[A-Z]{2}[A-Z0-9]{3}\d{7}$/'],
            'store_url' => ['array'],
            'store_url.*' => ['nullable', 'url', 'max:500'],
        ], [
            'upc.regex' => 'UPC/EAN must be 12–14 digits.',
            'isrc.*.regex' => 'ISRC format is CC-XXX-YY-NNNNN without dashes, e.g. NGA0D2600001.',
        ]);

        if (in_array($data['status'], ['approved', 'delivered', 'live'], true) && empty($data['upc'])) {
            return back()->withErrors(['upc' => 'Add the UPC before approving.']);
        }
        foreach ($release->tracks as $track) {
            $isrc = strtoupper(str_replace('-', '', (string) ($data['isrc'][$track->id] ?? '')));
            $track->forceFill(['isrc' => $isrc ?: null])->save();
            if (in_array($data['status'], ['approved', 'delivered', 'live'], true) && ! $isrc) {
                return back()->withErrors(['isrc' => "Add an ISRC for “{$track->title}” before approving."]);
            }
        }
        foreach ($data['store_url'] ?? [] as $storeId => $url) {
            $release->stores()->updateExistingPivot($storeId, ['url' => $url ?: null]);
        }

        $release->forceFill([
            'status' => $data['status'],
            'upc' => $data['upc'] ?? null,
            'rejection_reason' => $data['status'] === 'rejected' ? $data['rejection_reason'] : null,
        ])->save();

        return back()->with('success', 'Release updated: '.$release->statusLabel());
    }

    /** Download artwork, audio and a metadata sheet in one ZIP — ready to upload to your distribution partner. */
    public function package(Release $release)
    {
        abort_unless(class_exists(ZipArchive::class), 500, 'The PHP zip extension is not installed on this server.');
        $release->load('tracks', 'stores', 'user');
        @set_time_limit(600);

        $tmp = storage_path('app/tmp');
        @mkdir($tmp, 0775, true);
        $zipPath = $tmp.'/release-'.$release->id.'-'.Str::random(6).'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $disk = Storage::disk('local');

        if ($release->artwork_path && $disk->exists($release->artwork_path)) {
            $zip->addFile($disk->path($release->artwork_path), 'artwork.'.pathinfo($release->artwork_path, PATHINFO_EXTENSION));
        }
        foreach ($release->tracks as $t) {
            if ($t->audio_path && $disk->exists($t->audio_path)) {
                $zip->addFile($disk->path($t->audio_path), sprintf('%02d - %s.%s', $t->position, Str::slug($t->title), pathinfo($t->audio_path, PATHINFO_EXTENSION)));
            }
            if ($t->hasLyrics()) {
                $zip->addFromString(sprintf('lyrics/%02d - %s.txt', $t->position, Str::slug($t->title)), $t->lyrics);
            }
            if (trim((string) $t->lyrics_lrc) !== '') {
                $zip->addFromString(sprintf('lyrics/%02d - %s.lrc', $t->position, Str::slug($t->title)), $t->lyrics_lrc);
            }
        }
        $zip->addFromString('metadata.csv', $this->metadataCsv($release));
        $zip->addFromString('metadata.json', json_encode($this->metadata($release), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->close();

        return response()->download($zipPath, Str::slug($release->primary_artist.' '.$release->title).'.zip')->deleteFileAfterSend();
    }

    private function metadata(Release $r): array
    {
        return [
            'release' => [
                'id' => $r->id, 'type' => $r->type, 'title' => $r->title, 'primary_artist' => $r->primary_artist,
                'featured_artists' => $r->featured_artists, 'label' => $r->label_name ?: $r->primary_artist, 'genre' => $r->genre,
                'language' => $r->language, 'release_date' => $r->release_date?->toDateString(), 'explicit' => $r->explicit,
                'upc' => $r->upc, 'c_line' => $r->copyright_line, 'p_line' => $r->phonographic_line,
                'stores' => $r->stores->pluck('name'), 'account_email' => $r->user->email, 'notes' => $r->notes,
            ],
            'tracks' => $r->tracks->map(fn ($t) => [
                'position' => $t->position, 'title' => $t->title, 'version' => $t->version, 'featured_artists' => $t->featured_artists,
                'isrc' => $t->isrc, 'composers' => $t->composers, 'lyricists' => $t->lyricists, 'producers' => $t->producers,
                'explicit' => $t->explicit, 'language' => $t->language ?: $r->language, 'audio_file' => $t->audio_name,
                'has_lyrics' => $t->hasLyrics(), 'has_synced_lyrics' => trim((string) $t->lyrics_lrc) !== '',
            ])->all(),
        ];
    }

    private function metadataCsv(Release $r): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['upc', 'release_title', 'release_type', 'primary_artist', 'label', 'genre', 'release_date', 'track_no', 'track_title',
            'version', 'featured_artists', 'isrc', 'composers', 'lyricists', 'producers', 'explicit', 'language', 'c_line', 'p_line']);
        foreach ($r->tracks as $t) {
            fputcsv($out, [$r->upc, $r->title, $r->type, $r->primary_artist, $r->label_name ?: $r->primary_artist, $r->genre,
                $r->release_date?->toDateString(), $t->position, $t->title, $t->version, $t->featured_artists ?: $r->featured_artists,
                $t->isrc, $t->composers, $t->lyricists, $t->producers, $t->explicit ? 'yes' : 'no', $t->language ?: $r->language,
                $r->copyright_line, $r->phonographic_line]);
        }
        rewind($out);

        return stream_get_contents($out);
    }
}
