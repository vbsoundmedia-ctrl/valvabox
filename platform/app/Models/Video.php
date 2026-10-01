<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    protected $guarded = ['id', 'user_id', 'status', 'paid_at', 'submitted_at', 'rejection_reason', 'isrc', 'youtube_url', 'video_path', 'video_name', 'thumbnail_path'];

    protected function casts(): array
    {
        return ['explicit' => 'boolean', 'release_date' => 'date', 'paid_at' => 'datetime', 'submitted_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'pending_payment', 'rejected'], true);
    }

    public function statusLabel(): string
    {
        return Release::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
