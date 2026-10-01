<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['unlimited_releases' => 'boolean', 'is_featured' => 'boolean', 'is_active' => 'boolean'];
    }

    public static function free(): self
    {
        return static::where('price_kobo', 0)->orderBy('sort')->first()
            ?? new static(['name' => 'Starter', 'slug' => 'starter', 'price_kobo' => 0, 'single_fee_kobo' => 500000,
                'album_fee_kobo' => 1200000, 'royalty_share' => 85, 'videos_per_year' => 0]);
    }

    public function featureList(): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", (string) $this->features))));
    }
}
