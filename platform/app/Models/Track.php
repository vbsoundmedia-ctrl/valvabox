<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Track extends Model
{
    protected $guarded = ['id', 'release_id', 'audio_path', 'audio_name', 'audio_size', 'isrc'];

    protected function casts(): array
    {
        return ['explicit' => 'boolean'];
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function hasLyrics(): bool
    {
        return trim((string) $this->lyrics) !== '';
    }
}
