<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeatureAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    public function test_module_thresholds_protect_pages_and_every_maze_write(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        for ($level = 1; $level <= 5; $level++) {
            $user->levelProgress()->create(['level_id' => $level, 'status' => 'completed']);
        }
        foreach ([0, 3, 4, 7, 8, 10, 11] as $count) {
            $this->completeModules($user, $count);
            $this->get('/code-match')->assertRedirect('/modules');
            $this->get('/code-match/4')->assertRedirect('/modules');
            foreach ([1 => 4, 2 => 4, 3 => 8, 4 => 11, 5 => 11] as $level => $required) {
                $this->getJson('/maze/'.$level)->assertStatus($count >= $required ? 200 : 403);
                if ($count >= $required) {
                    continue;
                }
                $payload = ['level_id' => $level, 'code' => 'langkah = 1', 'tutorial_stage' => 0, 'version' => 1, 'run_version' => 1, 'request_id' => (string) Str::uuid()];
                $this->postJson('/game/progress/save', $payload)->assertForbidden();
                $this->postJson('/game/level/complete', $payload)->assertForbidden();
                $this->postJson('/game/'.$level.'/hint', ['version' => 1])->assertForbidden();
                $this->postJson('/game/story', ['version' => 1, 'level_id' => $level, 'story_state' => 'playing'])->assertForbidden();
                if ($level === 5) {
                    $this->postJson('/game/5/replay', ['version' => 1, 'run_version' => 1])->assertForbidden();
                }
            }
        }
        $this->assertDatabaseCount('code_submissions', 0);
        $this->assertDatabaseCount('code_match_results', 11);
    }

    public function test_unlock_notices_are_returned_once_at_each_milestone(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach (Lesson::turtleBasics()->learningOrder()->get() as $index => $lesson) {
            $features = match ($index + 1) {
                4 => ['Maze Quest level 1–2'],
                8 => ['Maze Quest level 3'],
                11 => ['Maze Quest level 4–5'],
                default => [],
            };
            $this->completeMatching($user, $lesson);
            $input = ['answer' => $lesson->learning_content['quiz']['answer']];
            $response = $this->postJson('/modules/'.$lesson->id.'/complete', $input)->assertOk()->assertJsonPath('unlocked_features', $features);
            if ($features) {
                $response->assertJsonPath('continue_url', '/maze')->assertJsonPath('continue_label', 'Buka Maze Quest');
            }
            $this->postJson('/modules/'.$lesson->id.'/complete', $input)->assertOk()->assertJsonPath('unlocked_features', []);
        }
    }

    public function test_legacy_lessons_and_gaps_do_not_unlock_games_and_html_has_popup(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->lessons()->attach(Lesson::turtleBasics()->learningOrder()->skip(1)->take(10)->pluck('id')->all());
        $this->getJson('/maze/1')->assertForbidden();
        $this->get('/code-match')->assertRedirect('/modules');
        $this->get('/modules')->assertOk()->assertDontSeeText('Code Matching terkunci.');
        $this->get('/maze/1')->assertRedirect('/maze')->assertSessionHas('feature_notice');
        $this->get('/game/continue')->assertRedirect('/maze');
        $this->get('/maze')->assertOk()->assertSeeText('Selesaikan sampai modul 4')->assertDontSee('href="/maze/1"', false);
        $this->completeModules($user, 4);
        $this->get('/maze/1')->assertOk()->assertSeeText('Perulangan dengan variabel')->assertSee('insertCode');
        $this->get('/maze/2')->assertForbidden();
    }
}
