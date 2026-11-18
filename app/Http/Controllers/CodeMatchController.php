<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\User;
use App\Services\CodeMatchService;
use App\Services\GameProgressService;
use App\Services\LessonAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CodeMatchController extends Controller
{
    public function submit(Request $r, Lesson $module, CodeMatchService $service, GameProgressService $progress, LessonAccessService $access)
    {
        $access->assertAccessible($r->user(), $module);
        abort_unless(count($service->pairs($module)) === 4, 404);
        $input = $r->validate(['request_id' => 'required|uuid', 'attempts' => 'required|array|min:4|max:200', 'attempts.*.description' => 'required|in:a,b,c,d', 'attempts.*.snippet' => 'required|in:a,b,c,d', 'user_id' => 'prohibited', 'score' => 'prohibited']);

        return DB::transaction(function () use ($r, $module, $input, $service, $progress, $access) {
            $u = User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            $access->assertAccessible($u, $module);
            if ($old = $u->codeMatchResults()->where('request_id', $input['request_id'])->first()) {
                abort_unless($old->lesson_id === $module->id, 409, 'Permintaan ini sudah digunakan pada modul lain.');

                return response()->json($old->only('score', 'accuracy') + $access->completionState($u, $module) + ['unlocked_features' => []]);
            }
            $result = $service->grade($input['attempts']);
            abort_unless($result['correct_answers'] === 4, 422, 'Selesaikan keempat pasangan terlebih dahulu.');
            $before = $access->completedCount($u);
            $u->codeMatchResults()->create($result + ['lesson_id' => $module->id, 'request_id' => $input['request_id'], 'completed_at' => now()]);
            $progress->syncXp($u);

            $features = $access->newlyUnlocked($before, $access->completedCount($u));
            $state = $access->completionState($u, $module);
            if ($state['completed'] && $features) {
                $state['continue_url'] = route('maze', [], false);
                $state['continue_label'] = 'Buka Maze Quest';
            }

            return response()->json($result + $state + ['unlocked_features' => $features]);
        });
    }
}
