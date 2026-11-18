<?php

namespace App\Services;

use App\Models\Level;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GameProgressService
{
    public function unlocked(User $user, int $level): bool
    {
        return $level === 1 || $user->levelProgress()->where('level_id', $level - 1)->where('status', 'completed')->exists();
    }

    public function syncXp(User $user): void
    {
        $match = $user->codeMatchResults()->selectRaw('lesson_id, MAX(score) as best')->groupBy('lesson_id')->get()->sum('best');
        $user->forceFill(['xp' => $user->levelProgress()->sum('best_score') + $match + $user->lessons()->count() * 20])->save();
    }

    public function submit(User $user, Level $level, array $input): array
    {
        return DB::transaction(function () use ($user, $level, $input) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($input['version'] === $user->adventure_version, 409, 'Petualangan telah dimulai ulang. Muat ulang halaman.');
            abort_unless($this->unlocked($user, $level->id), 403, 'Selesaikan level sebelumnya terlebih dahulu.');
            if ($old = $user->codeSubmissions()->where('request_id', $input['request_id'])->first()) {
                return $old->feedback;
            }
            $result = app(TurtleService::class)->run($input['code'], $level->definition);
            $p = $user->levelProgress()->firstOrCreate(['level_id' => $level->id]);
            $checkpoint = ($input['phase'] ?? 'final') === 'checkpoint';
            if (! $checkpoint) {
                $p->attempts++;
            }
            $p->code = $input['code'];
            if ($checkpoint && $result['result'] === 'SUCCESS') {
                $result['result'] = 'PARTIAL';
            }
            $score = $result['result'] === 'SUCCESS' ? max(60, 100 - $p->hints_used * 5 - min(20, max(0, $p->attempts - 1) * 2)) : 0;
            $p->score = $score;
            $p->best_score = max($p->best_score, $score);
            if ($result['result'] !== 'ERROR') {
                $p->state = $result['state'];
            }
            if ($result['result'] === 'SUCCESS') {
                $p->status = 'completed';
                $p->completed_at ??= now();
            }
            $p->save();
            $result['score'] = $score;
            $result['best_score'] = $p->best_score;
            if ($result['result'] !== 'ERROR') {
                $s = $result['state'];
                $user->gameProgress()->updateOrCreate([], ['current_level' => $level->id, 'current_checkpoint' => $s['checkpoint'], 'player_x' => $s['x'], 'player_y' => $s['y'], 'player_direction' => $s['angle'], 'story_state' => $result['result'] === 'SUCCESS' ? "outro:$level->id" : "playing:$level->id"]);
            }
            if ($result['result'] === 'SUCCESS') {
                $user->forceFill(['current_level' => max($user->current_level, min(3, $level->id + 1))])->save();
            }
            $user->codeSubmissions()->create(['level_id' => $level->id, 'request_id' => $input['request_id'], 'code' => $input['code'], 'result' => $result['result'], 'score' => $score, 'error_type' => $result['error_type'], 'feedback' => $result]);
            $this->syncXp($user);

            return $result;
        });
    }
}
