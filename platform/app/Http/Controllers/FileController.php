<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Models\Track;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;

/** Streams private files to their owner or an admin. Supports HTTP range requests (audio seeking). */
class FileController extends Controller
{
    private function send(?string $path, ?string $downloadName = null)
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        $full = Storage::disk('local')->path($path);

        return $downloadName
            ? response()->download($full, $downloadName)
            : response()->file($full, ['Cache-Control' => 'private, max-age=86400']);
    }

    public function artwork(Release $release, string $size = 'thumb')
    {
        $this->authorizeOwner($release);

        return $this->send($size === 'full' ? $release->artwork_path : ($release->artwork_thumb_path ?: $release->artwork_path));
    }

    public function audio(Track $track)
    {
        $this->authorizeOwner($track->release);

        return $this->send($track->audio_path, request()->boolean('download') ? ($track->audio_name ?: 'audio.wav') : null);
    }

    public function video(Video $video)
    {
        $this->authorizeOwner($video);

        return $this->send($video->video_path, request()->boolean('download') ? ($video->video_name ?: 'video.mp4') : null);
    }

    public function thumbnail(Video $video)
    {
        $this->authorizeOwner($video);

        return $this->send($video->thumbnail_path);
    }
}
