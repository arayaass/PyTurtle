<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelProgress extends Model
{
    protected $attributes = ['score' => 0, 'best_score' => 0, 'attempts' => 0, 'hints_used' => 0, 'status' => 'in_progress'];

    protected $table = 'level_progress';

    protected $fillable = ['level_id', 'status', 'score', 'best_score', 'attempts', 'hints_used', 'code', 'state', 'completed_at'];

    protected function casts(): array
    {
        return ['state' => 'array', 'completed_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}
