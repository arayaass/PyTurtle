<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Services\GameProgressService;
use App\Services\LessonAccessService;
use Illuminate\Http\Request;

class MazeController extends Controller
{
    public function index(Request $r, LessonAccessService $access, GameProgressService $service)
    {
        return view('maze.index', ['access' => $access, 'service' => $service, 'completedModules' => $access->completedCount($r->user()), 'levels' => Level::orderBy('id')->get(), 'progress' => $r->user()->levelProgress()->get()->keyBy('level_id')]);
    }

    public function show(Request $r, Level $level, GameProgressService $service, LessonAccessService $access)
    {
        if (! $access->mazeAllowed($r->user(), $level->id) && ! $r->expectsJson()) {
            return redirect('/maze')->with('feature_notice', $access->mazeMessage($level->id));
        }
        abort_unless($service->unlocked($r->user(), $level->id), 403, $access->mazeAllowed($r->user(), $level->id) ? 'Selesaikan level sebelumnya terlebih dahulu.' : $access->mazeMessage($level->id));
        $progress = $r->user()->levelProgress()->where('level_id', $level->id)->first();
        $definition = $level->stageDefinition($progress?->state);
        $definition['editor_commands'] = array_values(array_intersect(['forward', 'left', 'right'], $definition['commands']));
        unset($definition['solution'],$definition['hint']);

        return view('maze.show', ['level' => $level, 'definition' => $definition, 'progress' => $r->user()->levelProgress()->where('level_id', $level->id)->first(), 'settings' => $r->user()->setting, 'story' => $r->user()->gameProgress?->story_state]);
    }

    public function resume(Request $r, GameProgressService $service, LessonAccessService $access)
    {
        $p = $r->user()->gameProgress;
        $id = $p?->current_level ?? 1;
        if ($r->user()->levelProgress()->where('level_id', $id)->where('status', 'completed')->exists()) {
            $id = min(5, $id + 1);
        }

        if (! $service->unlocked($r->user(), $id)) {
            return redirect('/maze')->with('feature_notice', $access->mazeAllowed($r->user(), $id) ? 'Selesaikan level sebelumnya terlebih dahulu.' : $access->mazeMessage($id));
        }

        return redirect('/maze/'.$id);
    }
}
