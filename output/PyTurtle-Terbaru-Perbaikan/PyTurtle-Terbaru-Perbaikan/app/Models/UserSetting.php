<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $fillable = ['music_enabled', 'sound_enabled', 'reduced_motion'];

    protected function casts(): array
    {
        return ['music_enabled' => 'boolean', 'sound_enabled' => 'boolean', 'reduced_motion' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
