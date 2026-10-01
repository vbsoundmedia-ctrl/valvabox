<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'in_review');
        $q = Video::with('user');
        if ($status !== 'all') {
            $q->where('status', $status);
        }

        return view('admin.videos.index', ['videos' => $q->latest()->paginate(30)->withQueryString(), 'status' => $status]);
    }

    public function show(Video $video)
    {
        return view('admin.videos.show', ['video' => $video->load('user', 'track.release')]);
    }

    public function update(Request $request, Video $video)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Release::STATUSES))],
            'isrc' => ['nullable', 'regex:/^[A-Z]{2}[A-Z0-9]{3}\d{7}$/'],
            'youtube_url' => ['nullable', 'url'],
            'rejection_reason' => ['nullable', 'required_if:status,rejected', 'string', 'max:2000'],
        ]);
        $data['rejection_reason'] = $data['status'] === 'rejected' ? $data['rejection_reason'] : null;
        $video->forceFill($data)->save();

        return back()->with('success', 'Video updated.');
    }
}
