<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodeSubmission extends Model
{
    protected $fillable = ['level_id', 'request_id', 'code', 'result', 'score', 'error_type', 'feedback'];

    protected function casts(): array
    {
        return ['feedback' => 'array'];
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
