<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'display_name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token', 'google_id'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'xp' => 'integer', 'current_level' => 'integer', 'adventure_version' => 'integer'];
    }

    public function gameProgress()
    {
        return $this->hasOne(GameProgress::class);
    }

    public function levelProgress()
    {
        return $this->hasMany(LevelProgress::class);
    }

    public function codeSubmissions()
    {
        return $this->hasMany(CodeSubmission::class);
    }

    public function codeMatchResults()
    {
        return $this->hasMany(CodeMatchResult::class);
    }

    public function setting()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function lessons()
    {
        return $this->belongsToMany(Lesson::class)->withTimestamps();
    }
}
