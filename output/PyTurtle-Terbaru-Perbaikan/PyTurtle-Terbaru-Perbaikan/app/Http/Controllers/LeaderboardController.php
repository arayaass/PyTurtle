<?php

namespace App\Http\Controllers;

use App\Models\User;

class LeaderboardController extends Controller
{
    public function __invoke()
    {
        return view('leaderboard.index', ['leaders' => User::select('id', 'display_name', 'name', 'xp')->orderByDesc('xp')->orderBy('id')->paginate(20)]);
    }
}
