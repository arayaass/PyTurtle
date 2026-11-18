<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Services\GameProgressService;
use Illuminate\Http\Request;

class MazeController extends Controller
{
    public function index(Request $r)
    {
        return view('maze.index', ['levels' => Level::orderBy('id')->get(), 'progress' => $r->user()->levelProgress()->get()->keyBy('level_id')]);
    }

    public function show(Request $r, Level $level, GameProgressService $service)
    {
        abort_unless($service->unlocked($r->user(), $level->id), 403, 'Selesaikan level sebelumnya terlebih dahulu.');
        $definition = $level->definition;
        unset($definition['solution'],$definition['hint']);

        return view('maze.show', ['level' => $level, 'definition' => $definition, 'progress' => $r->user()->levelProgress()->where('level_id', $level->id)->first(), 'settings' => $r->user()->setting, 'story' => $r->user()->gameProgress?->story_state]);
    }

    public function resume(Request $r)
    {
        $p = $r->user()->gameProgress;
        $id = $p?->current_level ?? 1;
        if ($r->user()->levelProgress()->where('level_id', $id)->where('status', 'completed')->exists()) {
            $id = min(3, $id + 1);
        }

        return redirect('/maze/'.$id);
    }
}
