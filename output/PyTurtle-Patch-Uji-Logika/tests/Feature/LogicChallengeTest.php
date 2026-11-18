<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LogicChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LogicChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_require_login_and_render_without_exposing_answers(): void
    {
        $this->withoutVite();
        $this->get('/uji-logika')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/uji-logika')->assertOk()->assertSee('Uji Logika');
        $this->get('/uji-logika/1')->assertOk()->assertDontSee('"answer":0', false);
        $this->get('/uji-logika/99')->assertNotFound();
    }

    public function test_evidence_reason_ownership_and_idempotent_completion(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $id = (string) Str::uuid();
        $payload = ['angle' => 0, 'distance' => 2, 'request_id' => $id];
        $this->postJson('/uji-logika/1/experiment', $payload)->assertOk()->assertJsonPath('counterexample', false);
        $this->postJson('/uji-logika/1/answer', ['experiment_id' => $id, 'answer' => 0])->assertJsonPath('correct', false);
        $this->postJson('/uji-logika/1/experiment', $payload)->assertOk();
        $this->assertDatabaseCount('logic_experiments', 1);
        $payload['angle'] = 90;
        $this->postJson('/uji-logika/1/experiment', $payload)->assertConflict();
        $id = $payload['request_id'] = (string) Str::uuid();
        $this->postJson('/uji-logika/1/experiment', $payload)->assertOk()->assertJsonPath('counterexample', true)->assertJsonPath('a.y', 2);
        $this->postJson('/uji-logika/1/answer', ['experiment_id' => $id, 'answer' => 1])->assertJsonPath('correct', false);
        $this->assertDatabaseCount('logic_completions', 0);
        foreach ([1, 2] as $unused) {
            $this->postJson('/uji-logika/1/answer', ['experiment_id' => $id, 'answer' => 0])->assertJsonPath('correct', true);
        }
        $this->assertDatabaseCount('logic_completions', 1);
        $this->assertEquals(0, $user->fresh()->xp);
        $this->actingAs(User::factory()->create())->postJson('/uji-logika/1/answer', ['experiment_id' => $id, 'answer' => 0])->assertNotFound();
        $this->postJson('/uji-logika/1/experiment', ['angle' => 45, 'distance' => 100, 'request_id' => (string) Str::uuid()])->assertUnprocessable();
    }

    public function test_all_challenges_have_counterexamples_and_expected_geometry(): void
    {
        $service = app(LogicChallengeService::class);
        foreach ($service->challenges() as $id => $definition) {
            $found = false;
            foreach ([0, 90, 180, 270] as $angle) {
                foreach ([1, 2, 3, 4] as $distance) {
                    $found = $service->experiment($id, $angle, $distance)['counterexample'] || $found;
                }
            }
            $this->assertTrue($found, "Challenge $id");
        }
        $this->assertFalse($service->experiment(4, 0, 1)['counterexample']);
        $this->assertTrue($service->experiment(4, 0, 2)['counterexample']);
        $this->assertFalse($service->experiment(6, 90, 2)['counterexample']);
        $this->assertEquals(0, $service->experiment(5, 0, 2)['a']['angle']);
        $this->assertEquals(2,$service->experiment(2,0,2)['b']['y']);
    }
}
