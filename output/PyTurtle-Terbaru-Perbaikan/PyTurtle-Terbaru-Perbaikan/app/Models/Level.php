<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    protected $fillable = ['id', 'title', 'definition'];

    protected function casts(): array
    {
        return ['definition' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
