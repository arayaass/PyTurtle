<?php

namespace App\Services;

use App\Models\Level;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GameProgressService
{
    public function unlocked(User $user, int $level): bool
    {
        return app(LessonAccessService::class)->mazeAllowed($user, $level)
            && ($level === 1 || $user->levelProgress()->where('level_id', $level - 1)->where('status', 'completed')->exists());
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
            abort_unless($this->unlocked($user, $level->id), 403, app(LessonAccessService::class)->mazeAllowed($user, $level->id) ? 'Selesaikan level sebelumnya terlebih dahulu.' : app(LessonAccessService::class)->mazeMessage($level->id));
            $p = $user->levelProgress()->firstOrCreate(['level_id' => $level->id]);
            if ($level->id === 5) {
                abort_unless(($input['run_version'] ?? null) === $p->run_version, 409, 'Percobaan telah berubah. Muat ulang halaman sebelum melanjutkan.');
            }
            if ($old = $user->codeSubmissions()->where('request_id', $input['request_id'])->first()) {
                abort_unless($old->level_id === $level->id, 409);

                return $old->feedback;
            }
            if ($level->id === 5) {
                abort_if($p->run_completed, 409, 'Percobaan sudah selesai. Pilih Ulangi penilaian untuk mencoba lagi.');
            }
            $definition = $level->stageDefinition($p->state);
            if ($level->id === 1) {
                abort_unless(($input['tutorial_stage'] ?? -1) === $definition['tutorial_stage'], 409, 'Tahap tutorial telah berubah. Muat ulang halaman.');
            }
            $result = app(TurtleService::class)->run($input['code'], $definition);
            $stagePassed = $level->id === 1 && $result['result'] === 'SUCCESS' && $definition['tutorial_stage'] < 2 && ($input['phase'] ?? 'final') === 'final';
            $result['next_tutorial'] = $stagePassed;
            if ($level->id === 1) {
                $result['state']['tutorial_stage'] = $definition['tutorial_stage'] + ($stagePassed ? 1 : 0);
            }
            $checkpoint = ($input['phase'] ?? 'final') === 'checkpoint';
            if (! $checkpoint) {
                $p->attempts++;
            }
            $p->code = $input['code'];
            if ($checkpoint && $result['result'] === 'SUCCESS') {
                $result['result'] = 'PARTIAL';
            }
            if ($level->id === 5 && ! $checkpoint && $result['result'] !== 'SUCCESS') {
                $p->run_failures++;
            }
            $score = $result['result'] === 'SUCCESS' && ! $stagePassed ? max(60, 100 - $p->hints_used * 5 - min(20, max(0, $p->attempts - 1 - ($definition['tutorial_stage'] ?? 0)) * 2)) : 0;
            if ($level->id === 5 && $result['result'] === 'SUCCESS') {
                $rewards = app(FinalLevelRewardService::class);
                $score = $rewards->score($p);
                $p->run_completed = true;
                if ($rewards->perfect($p)) {
                    $p->perfect_completed_at ??= now();
                }
            }
            $previousBest = $p->best_score;
            $p->score = $score;
            $p->best_score = max($p->best_score, $score);
            if ($result['result'] !== 'ERROR') {
                $p->state = $result['state'];
            }
            if ($result['result'] === 'SUCCESS' && ! $stagePassed) {
                $p->status = 'completed';
                $p->completed_at ??= now();
            }
            $p->save();
            if (! $checkpoint && $result['result'] !== 'SUCCESS') {
                $failures = $user->codeSubmissions()->where('level_id', $level->id)->where('result', '!=', 'SUCCESS')->count() + 1;
                if ($failures % 3 === 0) {
                    $result['adaptive_hint'] = $definition['hint'];
                }
            }
            $result['score'] = $score;
            $result['best_score'] = $p->best_score;
            $result['xp_improvement'] = $p->best_score - $previousBest;
            $result['run_version'] = $p->run_version;
            $result['run_failures'] = $p->run_failures;
            $result['run_completed'] = $p->run_completed;
            $result['badge_earned'] = $level->id === 5 && $result['result'] === 'SUCCESS' && app(FinalLevelRewardService::class)->perfect($p);
            $result['has_badge'] = $p->perfect_completed_at !== null;
            if ($result['result'] !== 'ERROR') {
                $s = $result['state'];
                $user->gameProgress()->updateOrCreate([], ['current_level' => $level->id, 'current_checkpoint' => $s['checkpoint'], 'player_x' => $s['x'], 'player_y' => $s['y'], 'player_direction' => $s['angle'], 'story_state' => $result['result'] === 'SUCCESS' && ! $stagePassed ? "outro:$level->id" : "playing:$level->id"]);
            }
            if ($result['result'] === 'SUCCESS' && ! $stagePassed) {
                $user->forceFill(['current_level' => max($user->current_level, min(5, $level->id + 1))])->save();
            }
            $user->codeSubmissions()->create(['level_id' => $level->id, 'request_id' => $input['request_id'], 'code' => $input['code'], 'result' => $result['result'], 'score' => $score, 'error_type' => $result['error_type'], 'feedback' => $result]);
            $this->syncXp($user);

            return $result;
        });
    }
}
