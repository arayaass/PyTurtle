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
        $input = $r->validate(['level_id' => 'required|integer|exists:levels,id', 'code' => 'required|string|max:10000', 'phase' => 'sometimes|in:checkpoint,final', 'request_id' => 'required|uuid', 'version' => 'required|integer', 'user_id' => 'prohibited', 'score' => 'prohibited', 'status' => 'prohibited']);

        return response()->json($service->submit($r->user(), Level::findOrFail($input['level_id']), $input));
    }

    public function hint(Request $r, Level $level, GameProgressService $service)
    {
        $r->validate(['version' => 'required|integer']);

        return DB::transaction(function () use ($r, $level, $service) {
            $u = User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($r->integer('version') === $u->adventure_version, 409);
            abort_unless($service->unlocked($u, $level->id), 403);
            $p = $u->levelProgress()->firstOrCreate(['level_id' => $level->id]);
            $p->update(['hints_used' => 1]);

            return response()->json(['hint' => $level->definition['hint']]);
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

        return redirect('/maze')->with('status','Petualangan dimulai ulang. Materi, Code Match, dan akunmu tetap tersimpan.');
    }
}
