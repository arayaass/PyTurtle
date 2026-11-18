<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TutorialAdventureTest extends TestCase
{
    use RefreshDatabase;

    public function test_stages_are_persistent_and_cannot_be_skipped(): void
    {
        $this->seed();
        $this->withoutVite();
        $user = User::factory()->create();
        $this->completeModules($user, 4);
        $this->actingAs($user);
        $level = Level::findOrFail(1);
        $submit = fn ($stage, $code, $id = null) => $this->postJson('/game/progress/save', ['level_id' => 1, 'tutorial_stage' => $stage, 'code' => $code, 'request_id' => $id ?? (string) Str::uuid(), 'version' => 1, 'run_version' => 1]);
        $submit(2, $level->definition['tutorial_stages'][2]['solution'])->assertConflict();
        for ($stage = 0; $stage < 3; $stage++) {
            $id = (string) Str::uuid();
            $code = $level->definition['tutorial_stages'][$stage]['solution'];
            $submit($stage, $code, $id)->assertOk()->assertJsonPath('next_tutorial', $stage < 2);
            $submit($stage, $code, $id)->assertOk();
            if ($stage < 2) {
                $this->get('/maze/2')->assertForbidden();
                $this->get('/maze/1')->assertViewHas('definition', fn ($d) => $d['tutorial_stage'] === $stage + 1 && ! isset($d['solution']) && ! isset($d['tutorial_stages']));
                $submit($stage, $code)->assertConflict();
            }
        }
        $this->get('/maze/2')->assertOk();
        $this->assertEquals(180, $user->fresh()->xp);
        $this->assertDatabaseHas('level_progress', ['user_id' => $user->id, 'level_id' => 1, 'attempts' => 3, 'best_score' => 100]);
    }

    public function test_hint_only_arrives_every_third_unsuccessful_attempt(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $this->completeModules($user, 4);
        $this->actingAs($user);
        for ($n = 1; $n <= 4; $n++) {
            $r = $this->postJson('/game/progress/save', ['level_id' => 1, 'tutorial_stage' => 0, 'code' => 'forward(3)', 'request_id' => (string) Str::uuid(), 'version' => 1]);
            $r->assertOk()->assertJsonPath('result', 'PARTIAL')->assertJsonPath('error_type', 'variable_required');
            if ($n === 3) {
                $r->assertJsonStructure(['adaptive_hint']);
            } else {
                $r->assertJsonMissingPath('adaptive_hint');
            }
        }
        $this->get('/maze/2')->assertForbidden();
    }
}
