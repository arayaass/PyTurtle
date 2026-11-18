<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodeMatchResult extends Model
{
    protected $fillable = ['lesson_id', 'request_id', 'correct_answers', 'wrong_answers', 'accuracy', 'score', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
