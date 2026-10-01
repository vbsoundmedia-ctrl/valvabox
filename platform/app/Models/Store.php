<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['supports_video' => 'boolean', 'is_active' => 'boolean'];
    }
}
