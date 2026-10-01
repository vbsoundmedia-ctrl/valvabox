<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Models\Store;
use App\Payments\PaymentService;
use App\Services\Uploads;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReleaseController extends Controller
{
    public const GENRES = ['Afrobeats', 'Afro-pop', 'Amapiano', 'Afro-fusion', 'Hip-Hop/Rap', 'R&B/Soul', 'Gospel', 'Highlife',
        'Fuji', 'Juju', 'Street-pop', 'Reggae/Dancehall', 'Pop', 'Alternative', 'Electronic', 'Jazz', 'Instrumental', 'Worship', 'Other'];

    public const LANGUAGES = ['English', 'Nigerian Pidgin', 'Yoruba', 'Igbo', 'Hausa', 'French', 'Swahili', 'Twi', 'Zulu', 'Instrumental', 'Other'];

    public function index(Request $request)
    {
        return view('artist.releases.index', ['releases' => $request->user()->releases()->withCount('tracks')->latest()->paginate(20)]);
    }

    public function create(Request $request)
    {
        return view('artist.releases.form', ['release' => new Release([
            'type' => 'single', 'primary_artist' => $request->user()->displayName(), 'language' => 'English',
            'release_date' => now()->addDays(14),
            'copyright_line' => now()->year.' '.$request->user()->displayName(),
            'phonographic_line' => now()->year.' '.$request->user()->displayName(),
        ])]);
    }

    private function rules(bool $artworkRequired): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(Release::TYPES))],
            'title' => ['required', 'string', 'max:200'],
            'primary_artist' => ['required', 'string', 'max:200'],
            'featured_artists' => ['nullable', 'string', 'max:255'],
            'label_name' => ['nullable', 'string', 'max:200'],
            'genre' => ['required', Rule::in(self::GENRES)],
            'language' => ['required', Rule::in(self::LANGUAGES)],
            'release_date' => ['required', 'date', 'after_or_equal:'.now()->addDays(3)->toDateString()],
            'explicit' => ['boolean'],
            'copyright_line' => ['required', 'string', 'max:200'],
            'phonographic_line' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'artwork' => [$artworkRequired ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png', 'max:30720',
                'dimensions:min_width=3000,min_height=3000,max_width=6000,max_height=6000,ratio=1'],
        ];
    }

    private function messages(): array
    {
        return [
            'artwork.dimensions' => 'Artwork must be a square image, at least 3000×3000 pixels.',
            'release_date.after_or_equal' => 'Pick a release date at least 3 days from today so stores have time to process it (2–3 weeks is best).',
        ];
    }

    public function store(Request $request, Uploads $uploads)
    {
        $data = $request->validate($this->rules(true), $this->messages());
        $data['explicit'] = $request->boolean('explicit');
        unset($data['artwork']);

        $release = $request->user()->releases()->create($data);
        $this->saveArtwork($request, $release, $uploads);
        $release->stores()->sync(Store::where('is_active', true)->pluck('id'));

        return redirect()->route('releases.show', $release)->with('success', 'Release created. Now add your track(s).');
    }

    public function show(Release $release, PaymentService $payments)
    {
        $this->authorizeOwner($release);
        $release->load('tracks', 'stores');

        return view('artist.releases.show', [
            'release' => $release,
            'stores' => Store::where('is_active', true)->orderBy('sort')->get(),
            'problems' => $release->readinessProblems(),
            'fee' => $payments->releaseFee($release->user, $release),
        ]);
    }

    public function edit(Release $release)
    {
        $this->authorizeOwner($release);
        abort_unless($release->isEditable(), 403, 'This release is locked while it is being reviewed or is live.');

        return view('artist.releases.form', ['release' => $release]);
    }

    public function update(Request $request, Release $release, Uploads $uploads)
    {
        $this->authorizeOwner($release);
        abort_unless($release->isEditable(), 403);
        $rules = $this->rules(! $release->artwork_path);
        $data = $request->validate($rules, $this->messages());
        $data['explicit'] = $request->boolean('explicit');
        unset($data['artwork']);

        $release->update($data);
        $this->saveArtwork($request, $release, $uploads);

        return redirect()->route('releases.show', $release)->with('success', 'Release updated.');
    }

    public function destroy(Release $release, Uploads $uploads)
    {
        $this->authorizeOwner($release);
        abort_unless(in_array($release->status, ['draft', 'pending_payment', 'rejected'], true) && ! $release->paid_at, 403, 'Only unpaid drafts can be deleted.');
        foreach ($release->tracks as $t) {
            $uploads->delete($t->audio_path);
        }
        $uploads->delete($release->artwork_path, $release->artwork_thumb_path);
        $release->delete();

        return redirect()->route('releases.index')->with('success', 'Release deleted.');
    }

    public function stores(Request $request, Release $release)
    {
        $this->authorizeOwner($release);
        abort_unless($release->isEditable(), 403);
        $ids = $request->validate(['stores' => ['array'], 'stores.*' => ['integer', 'exists:stores,id']])['stores'] ?? [];
        $release->stores()->sync($ids);

        return back()->with('success', 'Stores saved.');
    }

    public function submit(Request $request, Release $release, PaymentService $payments)
    {
        $this->authorizeOwner($release);
        abort_unless($release->isEditable(), 403);
        $release->load('tracks');
        if ($problems = $release->readinessProblems()) {
            return back()->withErrors(['submit' => $problems]);
        }
        $request->validate(['confirm_rights' => ['accepted']], ['confirm_rights.accepted' => 'Please confirm you own the rights to this music.']);

        $fee = $payments->releaseFee($request->user(), $release);
        if ($fee === 0) {
            $release->forceFill(['status' => 'in_review', 'submitted_at' => now(), 'rejection_reason' => null])->save();

            return redirect()->route('releases.show', $release)->with('success', 'Submitted! Our team will review it within 1–2 working days.');
        }

        if (! $payments->gateway()->isConfigured()) {
            return back()->withErrors(['submit' => 'Online payment is not set up yet. Please contact support.']);
        }
        $release->forceFill(['status' => 'pending_payment'])->save();
        $payment = $payments->createPayment($request->user(), 'release', $release->id, $fee, $release->typeLabel().' release: '.$release->title);

        return app(PaymentController::class)->redirectToGateway($payment);
    }

    private function saveArtwork(Request $request, Release $release, Uploads $uploads): void
    {
        if (! $request->hasFile('artwork')) {
            return;
        }
        $uploads->delete($release->artwork_path, $release->artwork_thumb_path);
        $path = $uploads->store($request->file('artwork'), "releases/{$release->id}");
        $release->forceFill(['artwork_path' => $path, 'artwork_thumb_path' => $uploads->thumbnail($path)])->save();
    }
}
