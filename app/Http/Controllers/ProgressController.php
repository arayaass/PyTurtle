<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Level;
use App\Services\LessonAccessService;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function __invoke(Request $r)
    {
        return view('progress.index', ['levels' => Level::orderBy('id')->get(), 'progress' => $r->user()->levelProgress()->get()->keyBy('level_id'), 'lessonCount' => count(app(LessonAccessService::class)->forUser($r->user())['done']), 'lessonTotal' => Lesson::turtleBasics()->count()]);
    }
}
