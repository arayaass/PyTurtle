<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinalRewardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
        $user = User::factory()->create();
        $this->completeModules($user, 11);
        $user->levelProgress()->create(['level_id' => 4, 'status' => 'completed']);
        $this->actingAs($user);
    }

    private function submit(?string $code = null, int $run = 1, array $extra = [])
    {
        return $this->postJson('/game/progress/save', array_merge([
            'level_id' => 5, 'code' => $code ?? Level::findOrFail(5)->definition['solution'],
            'request_id' => (string) Str::uuid(), 'version' => 1, 'run_version' => $run,
        ], $extra));
    }

    public function test_replay_improves_reward_without_stacking_and_earns_badge(): void
    {
        $this->submit('invalid()')->assertOk()->assertJsonPath('run_failures', 1);
        $this->submit()->assertOk()->assertJsonPath('score', 95)->assertJsonPath('badge_earned', false);
        $this->assertEquals(315, auth()->user()->fresh()->xp);
        $this->get('/leaderboard')->assertDontSee('✦ Turtle Master');
        $this->postJson('/game/5/replay', ['version' => 1, 'run_version' => 1])->assertOk()->assertJsonPath('run_version', 2);
        $this->submit(null, 1)->assertConflict();
        $id = (string) Str::uuid();
        $this->submit(null, 2, ['request_id' => $id])->assertOk()->assertJsonPath('score', 100)->assertJsonPath('xp_improvement', 5)->assertJsonPath('badge_earned', true);
        $this->submit(null, 2, ['request_id' => $id])->assertOk();
        $this->assertEquals(320, auth()->user()->fresh()->xp);
        $this->submit(null, 2)->assertConflict();
        $this->get('/leaderboard')->assertSee('Turtle Master');
        $this->postJson('/game/5/replay', ['version' => 1, 'run_version' => 2])->assertOk();
        $this->submit(null, 2, ['request_id' => $id])->assertConflict();
        $this->submit('forward(1)', 3)->assertOk();
        $this->submit(null, 3)->assertJsonPath('score', 95)->assertJsonPath('best_score', 100)->assertJsonPath('has_badge', true);
        $this->assertEquals(320, auth()->user()->fresh()->xp);
    }

    public function test_checkpoint_is_not_a_failure_and_hint_penalty_applies_once(): void
    {
        $this->submit('forward(2)', 1, ['phase' => 'checkpoint'])->assertOk()->assertJsonPath('run_failures', 0);
        foreach ([1, 2] as $unused) {
            $this->postJson('/game/5/hint', ['version' => 1, 'run_version' => 1])->assertOk();
        }
        $this->submit()->assertJsonPath('score', 95)->assertJsonPath('badge_earned', false);
        $this->postJson('/game/5/replay', ['version' => 1, 'run_version' => 1])->assertOk();
        $this->postJson('/game/5/hint', ['version' => 1, 'run_version' => 1])->assertConflict();
        $this->submit(null, 2)->assertJsonPath('score', 100)->assertJsonPath('badge_earned', true);
    }

    public function test_reward_has_floor_and_replay_requires_unlocked_level(): void
    {
        for ($i = 0; $i < 9; $i++) {
            $this->submit('forward(1)')->assertOk();
        }
        $this->submit()->assertJsonPath('score', 60);
        $this->actingAs(User::factory()->create())->postJson('/game/5/replay', ['version' => 1, 'run_version' => 1])->assertForbidden();
    }
}
