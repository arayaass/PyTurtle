<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\User;
use App\Services\GameProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameProgressController extends Controller
{
    public function save(Request $r, GameProgressService $service)
    {
        $input = $r->validate(['level_id' => 'required|integer|exists:levels,id', 'code' => 'required|string|max:10000', 'phase' => 'sometimes|in:checkpoint,final', 'request_id' => 'required|uuid', 'version' => 'required|integer', 'run_version' => 'required_if:level_id,3|integer|min:1', 'user_id' => 'prohibited', 'score' => 'prohibited', 'status' => 'prohibited']);

        return response()->json($service->submit($r->user(), Level::findOrFail($input['level_id']), $input));
    }

    public function hint(Request $r, Level $level, GameProgressService $service)
    {
        $r->validate(['version' => 'required|integer', 'run_version' => 'sometimes|integer|min:1']);

        return DB::transaction(function () use ($r, $level, $service) {
            $u = User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($r->integer('version') === $u->adventure_version, 409);
            abort_unless($service->unlocked($u, $level->id), 403);
            $p = $u->levelProgress()->firstOrCreate(['level_id' => $level->id]);
            if ($level->id === 3) {
                abort_unless($r->integer('run_version') === $p->run_version, 409, 'Percobaan telah berubah. Muat ulang halaman.');
                abort_if($p->run_completed, 409, 'Mulai penilaian baru sebelum menggunakan hint.');
                $p->run_hint_used = true;
            }
            $p->update(['hints_used' => 1]);

            return response()->json(['hint' => $level->definition['hint']]);
        });
    }

    public function replay(Request $r, Level $level, GameProgressService $service)
    {
        abort_unless($level->id === 3, 404);
        $input = $r->validate(['version' => 'required|integer', 'run_version' => 'required|integer|min:1']);

        return DB::transaction(function () use ($r, $level, $service, $input) {
            $user = User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($input['version'] === $user->adventure_version, 409, 'Petualangan telah dimulai ulang.');
            abort_unless($service->unlocked($user, $level->id), 403);
            $progress = $user->levelProgress()->firstOrCreate(['level_id' => $level->id]);
            abort_unless($input['run_version'] === $progress->run_version, 409, 'Penilaian baru sudah dimulai. Muat ulang halaman.');
            $progress->forceFill([
                'run_version' => $progress->run_version + 1,
                'run_failures' => 0, 'run_hint_used' => false, 'run_completed' => false,
                'code' => '', 'state' => null, 'score' => 0,
            ])->save();
            $start = $level->definition['start'];
            $user->gameProgress()->updateOrCreate([], [
                'current_level' => 3, 'current_checkpoint' => 0, 'player_x' => $start['x'],
                'player_y' => $start['y'], 'player_direction' => $start['angle'], 'story_state' => 'playing:3',
            ]);

            return response()->json(['run_version' => $progress->run_version, 'best_score' => $progress->best_score]);
        });
    }

    public function story(Request $r, GameProgressService $service)
    {
        $data = $r->validate(['level_id' => 'required|integer|exists:levels,id', 'version' => 'required|integer', 'story_state' => 'required|in:playing,finished']);

        return DB::transaction(function () use ($r, $data, $service) {
            $u = User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($data['version'] === $u->adventure_version, 409);
            abort_unless($service->unlocked($u, $data['level_id']), 403);
            if ($data['story_state'] === 'finished') {
                abort_unless($u->levelProgress()->where('level_id', $data['level_id'])->where('status', 'completed')->exists(), 422);
            }
            $values = ['current_level' => $data['level_id'], 'story_state' => $data['story_state'].':'.$data['level_id']];
            if ($u->gameProgress?->current_level !== $data['level_id']) {
                $state = $u->levelProgress()->where('level_id', $data['level_id'])->first()?->state ?? Level::findOrFail($data['level_id'])->definition['start'];
                $values += ['current_checkpoint' => $state['checkpoint'] ?? 0, 'player_x' => $state['x'], 'player_y' => $state['y'], 'player_direction' => $state['angle']];
            }
            $u->gameProgress()->updateOrCreate([], $values);

            return response()->json(['saved' => true]);
        });
    }

    public function restart(Request $r, GameProgressService $service)
    {
        $r->validate(['confirm' => 'accepted']);
        DB::transaction(function () use ($r, $service) {
            $u = User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            $u->gameProgress()->delete();
            $u->levelProgress()->delete();
            $u->codeSubmissions()->delete();
            $u->forceFill(['current_level' => 1, 'adventure_version' => $u->adventure_version + 1])->save();
            $service->syncXp($u);
        });

        return redirect('/maze')->with('status', 'Petualangan dimulai ulang. Materi, Code Match, dan akunmu tetap tersimpan.');
    }
}
