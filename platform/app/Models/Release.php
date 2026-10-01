<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Release extends Model
{
    public const TYPES = ['single' => 'Single', 'ep' => 'EP', 'album' => 'Album'];

    public const STATUSES = [
        'draft' => 'Draft',
        'pending_payment' => 'Awaiting payment',
        'in_review' => 'In review',
        'approved' => 'Approved',
        'delivered' => 'Delivered to stores',
        'live' => 'Live',
        'rejected' => 'Needs changes',
        'takedown' => 'Taken down',
    ];

    protected $guarded = ['id', 'user_id', 'status', 'upc', 'paid_at', 'submitted_at', 'rejection_reason'];

    protected function casts(): array
    {
        return ['release_date' => 'date', 'explicit' => 'boolean', 'paid_at' => 'datetime', 'submitted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class)->orderBy('position');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)->withPivot('url');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'pending_payment', 'rejected'], true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    /** Problems that block submission; empty means ready. */
    public function readinessProblems(): array
    {
        $problems = [];
        if (! $this->artwork_path) {
            $problems[] = 'Upload cover artwork (3000×3000 JPG or PNG).';
        }
        $tracks = $this->tracks;
        if ($tracks->isEmpty()) {
            $problems[] = 'Add at least one track.';
        }
        foreach ($tracks as $t) {
            if (! $t->audio_path) {
                $problems[] = "Track “{$t->title}” has no audio file.";
            }
        }
        if ($this->type === 'single' && $tracks->count() > 3) {
            $problems[] = 'A single can have at most 3 tracks. Change the type to EP or Album.';
        }
        if ($this->stores()->count() === 0) {
            $problems[] = 'Choose at least one store.';
        }
        if (! $this->release_date) {
            $problems[] = 'Set a release date.';
        }

        return $problems;
    }
}
