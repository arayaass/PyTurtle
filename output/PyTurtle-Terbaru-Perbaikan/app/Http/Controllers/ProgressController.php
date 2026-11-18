<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Lesson;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function __invoke(Request $r)
    {
        return view('progress.index', ['levels' => Level::orderBy('id')->get(), 'progress' => $r->user()->levelProgress()->get()->keyBy('level_id'), 'lessonCount' => $r->user()->lessons()->turtleBasics()->count(), 'lessonTotal' => Lesson::turtleBasics()->count()]);
    }
}
