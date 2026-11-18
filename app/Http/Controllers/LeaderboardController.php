<?php

namespace App\Http\Controllers;

use App\Models\User;

class LeaderboardController extends Controller
{
    public function __invoke()
    {
        return view('leaderboard.index', ['leaders' => User::select('id', 'display_name', 'name', 'xp')
            ->withExists(['levelProgress as has_turtle_badge' => fn ($query) => $query->where('level_id', 5)->whereNotNull('perfect_completed_at')])
            ->orderByDesc('xp')->orderBy('id')->paginate(20)]);
    }
}
