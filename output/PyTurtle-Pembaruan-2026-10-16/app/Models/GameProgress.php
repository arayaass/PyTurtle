<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameProgress extends Model
{
    protected $table = 'game_progress';

    protected $fillable = ['current_level', 'current_checkpoint', 'player_x', 'player_y', 'player_direction', 'story_state'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
