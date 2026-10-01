<?php

namespace App\Http\Controllers;

use App\Models\Track;
use App\Models\Video;
use App\Payments\PaymentService;
use App\Services\Uploads;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function index(Request $request, PaymentService $payments)
    {
        return view('artist.videos.index', ['videos' => $request->user()->videos()->latest()->paginate(20)]);
    }

    private function tracks(Request $request)
    {
        return Track::whereHas('release', fn ($q) => $q->where('user_id', $request->user()->id))->with('release')->latest()->get();
    }

    public function create(Request $request)
    {
        return view('artist.videos.form', ['video' => new Video(['artist' => $request->user()->displayName()]), 'tracks' => $this->tracks($request)]);
    }

    private function validated(Request $request, ?Video $video = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'artist' => ['required', 'string', 'max:200'],
            'director' => ['nullable', 'string', 'max:200'],
            'track_id' => ['nullable', 'integer'],
            'release_date' => ['required', 'date', 'after_or_equal:today'],
            'explicit' => ['boolean'],
            'video' => ['nullable', 'file', 'extensions:mp4,mov,m4v', 'max:4194304'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'thumbnail' => [$video?->thumbnail_path ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png', 'max:10240', 'dimensions:min_width=1280,min_height=720'],
        ], [
            'video.uploaded' => 'The video is bigger than your server accepts. Upload it to Google Drive/Dropbox and paste the share link instead.',
            'thumbnail.dimensions' => 'Thumbnail must be at least 1280×720.',
        ]);
        if (! empty($data['track_id'])) {
            abort_unless($this->tracks($request)->contains('id', (int) $data['track_id']), 422);
        }
        $data['explicit'] = $request->boolean('explicit');

        return $data;
    }

    private function saveFiles(Request $request, Video $video, Uploads $uploads): void
    {
        if ($request->hasFile('video')) {
            $uploads->delete($video->video_path);
            $video->video_path = $uploads->store($request->file('video'), "videos/{$video->id}");
            $video->video_name = mb_substr($request->file('video')->getClientOriginalName(), 0, 250);
        }
        if ($request->hasFile('thumbnail')) {
            $uploads->delete($video->thumbnail_path);
            $video->thumbnail_path = $uploads->store($request->file('thumbnail'), "videos/{$video->id}");
        }
        $video->save();
    }

    public function store(Request $request, Uploads $uploads)
    {
        $data = $this->validated($request);
        if (! $request->hasFile('video') && empty($data['video_url'])) {
            return back()->withInput()->withErrors(['video' => 'Upload the video file or paste a download link to it.']);
        }
        unset($data['video'], $data['thumbnail']);
        $video = $request->user()->videos()->create($data);
        $this->saveFiles($request, $video, $uploads);

        return redirect()->route('videos.show', $video)->with('success', 'Video saved. Review it and submit when ready.');
    }

    public function show(Video $video, PaymentService $payments)
    {
        $this->authorizeOwner($video);

        return view('artist.videos.show', ['video' => $video, 'fee' => $payments->videoFee($video->user, $video)]);
    }

    public function edit(Request $request, Video $video)
    {
        $this->authorizeOwner($video);
        abort_unless($video->isEditable(), 403);

        return view('artist.videos.form', ['video' => $video, 'tracks' => $this->tracks($request)]);
    }

    public function update(Request $request, Video $video, Uploads $uploads)
    {
        $this->authorizeOwner($video);
        abort_unless($video->isEditable(), 403);
        $data = $this->validated($request, $video);
        unset($data['video'], $data['thumbnail']);
        $video->update($data);
        $this->saveFiles($request, $video, $uploads);

        return redirect()->route('videos.show', $video)->with('success', 'Video updated.');
    }

    public function destroy(Video $video, Uploads $uploads)
    {
        $this->authorizeOwner($video);
        abort_unless($video->isEditable() && ! $video->paid_at, 403);
        $uploads->delete($video->video_path, $video->thumbnail_path);
        $video->delete();

        return redirect()->route('videos.index')->with('success', 'Video deleted.');
    }

    public function submit(Request $request, Video $video, PaymentService $payments)
    {
        $this->authorizeOwner($video);
        abort_unless($video->isEditable(), 403);
        $request->validate(['confirm_rights' => ['accepted']], ['confirm_rights.accepted' => 'Please confirm you own the rights to this video.']);

        $fee = $payments->videoFee($request->user(), $video);
        if ($fee === 0) {
            $video->forceFill(['status' => 'in_review', 'submitted_at' => now(), 'rejection_reason' => null])->save();

            return back()->with('success', 'Video submitted for review.');
        }
        if (! $payments->gateway()->isConfigured()) {
            return back()->withErrors(['submit' => 'Online payment is not set up yet. Please contact support.']);
        }
        $video->forceFill(['status' => 'pending_payment'])->save();
        $payment = $payments->createPayment($request->user(), 'video', $video->id, $fee, 'Music video: '.$video->title);

        return app(PaymentController::class)->redirectToGateway($payment);
    }
}
