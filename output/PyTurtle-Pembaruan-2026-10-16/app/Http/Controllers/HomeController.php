<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Level;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $r)
    {
        $user = $r->user()->load('levelProgress', 'lessons');

        return view('home.index', ['user' => $user, 'levels' => Level::orderBy('id')->get(), 'lessons' => Lesson::turtleBasics()->learningOrder()->get(), 'completed' => $user->levelProgress->where('status', 'completed')->count()]);
    }
}
