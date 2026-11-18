<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['id', 'title', 'command', 'description', 'example', 'explanation', 'practice_level', 'matching_pairs', 'curriculum_key', 'sort_order', 'learning_content'];

    protected function casts(): array
    {
        return ['matching_pairs' => 'array', 'learning_content' => 'array', 'sort_order' => 'integer'];
    }

    public function scopeTurtleBasics(Builder $query): Builder
    {
        return $query->where('lessons.curriculum_key', 'like', 'runestone-turtle-%');
    }

    public function scopeLearningOrder(Builder $query): Builder
    {
        return $query->orderBy('lessons.sort_order')->orderBy('lessons.id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
