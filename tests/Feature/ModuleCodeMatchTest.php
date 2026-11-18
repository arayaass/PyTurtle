<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use App\Services\CodeMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModuleCodeMatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    private function payload(): array
    {
        return ['request_id' => (string) Str::uuid(), 'attempts' => array_map(
            fn ($id) => ['description' => $id, 'snippet' => $id], ['a', 'b', 'c', 'd']
        )];
    }

    public function test_matching_opens_in_first_module_without_four_completions_but_respects_module_locks(): void
    {
        $user = User::factory()->create();
        [$first, $second] = Lesson::turtleBasics()->learningOrder()->take(2)->get()->all();
        $this->actingAs($user)->get('/modules/'.$first->id)->assertOk()
            ->assertSee('module-code-match')->assertSeeText('Code Matching · '.$first->title);
        $this->postJson('/modules/'.$first->id.'/code-match', $this->payload())->assertOk()->assertJsonPath('score', 100);
        $this->postJson('/modules/'.$second->id.'/code-match', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('code_match_results', 1);
        $this->get('/home')->assertDontSee('href="/code-match"', false);
        $this->get('/code-match')->assertRedirect('/modules');
        $this->postJson('/code-match/submit', $this->payload())->assertNotFound();
    }

    public function test_each_module_has_its_own_questions_and_saves_results_under_its_own_lesson(): void
    {
        $user = User::factory()->create();
        $this->completeModules($user);
        $this->actingAs($user);
        $sets = [];
        foreach (Lesson::turtleBasics()->learningOrder()->get() as $lesson) {
            $pairs = app(CodeMatchService::class)->pairs($lesson);
            $this->assertCount(4, $pairs);
            $sets[] = json_encode($pairs);
            $this->get('/modules/'.$lesson->id)->assertOk()->assertViewHas('matchingPairs', $pairs);
            $this->postJson('/modules/'.$lesson->id.'/code-match', $this->payload())->assertOk();
            $this->assertDatabaseHas('code_match_results', ['user_id' => $user->id, 'lesson_id' => $lesson->id, 'score' => 100]);
        }
        $this->assertCount(11, array_unique($sets));
        $this->assertEquals(1320, $user->fresh()->xp);
    }

    public function test_retry_and_replay_do_not_duplicate_xp_and_request_ids_cannot_cross_modules(): void
    {
        $user = User::factory()->create();
        $user->lessons()->attach(Lesson::turtleBasics()->learningOrder()->firstOrFail()->id);
        [$first, $second] = Lesson::turtleBasics()->learningOrder()->take(2)->get()->all();
        $payload = $this->payload();
        $url = '/modules/'.$first->id.'/code-match';
        $this->actingAs($user)->postJson($url, $payload)->assertOk();
        $this->postJson($url, $payload)->assertOk();
        $this->assertDatabaseCount('code_match_results', 1);
        $this->postJson($url, $this->payload())->assertOk();
        $this->assertEquals(120, $user->fresh()->xp);
        $this->postJson('/modules/'.$second->id.'/code-match', $payload)->assertConflict();
        $this->postJson('/modules/'.$second->id.'/code-match', $this->payload())->assertOk();
        $this->assertEquals(220, $user->fresh()->xp);
    }

    public function test_incomplete_or_unknown_pairs_and_client_supplied_score_are_rejected(): void
    {
        $user = User::factory()->create();
        $first = Lesson::turtleBasics()->learningOrder()->firstOrFail();
        $this->actingAs($user);
        $url = '/modules/'.$first->id.'/code-match';
        $payload = $this->payload();
        $payload['attempts'][3]['snippet'] = 'a';
        $this->postJson($url, $payload)->assertUnprocessable();
        $payload['attempts'][3]['snippet'] = 'unknown';
        $this->postJson($url, $payload)->assertUnprocessable();
        $this->postJson($url, $this->payload() + ['score' => 100])->assertUnprocessable();
        $this->assertDatabaseCount('code_match_results', 0);
    }

    public function test_quiz_alone_keeps_next_module_locked_until_matching_is_saved(): void
    {
        $user = User::factory()->create();
        [$first, $second] = Lesson::turtleBasics()->learningOrder()->take(2)->get()->all();
        $this->actingAs($user)->postJson('/modules/'.$first->id.'/complete', ['answer' => $first->learning_content['quiz']['answer']])
            ->assertOk()->assertJsonPath('correct', true)->assertJsonPath('completed', false)
            ->assertJsonPath('continue_url', null)
            ->assertJsonPath('quiz_passed', true)->assertJsonPath('unlocked_lessons', [$first->id]);
        $this->getJson('/modules/'.$second->id)->assertForbidden();
        $this->postJson('/modules/'.$second->id.'/complete', ['answer' => $second->learning_content['quiz']['answer']])->assertForbidden();
        $this->postJson('/modules/'.$second->id.'/code-match', $this->payload())->assertForbidden();
        $input = $this->payload();
        $this->postJson('/modules/'.$first->id.'/code-match', $input)->assertOk()
            ->assertJsonPath('continue_url', '/modules/'.$second->id)
            ->assertJsonPath('completed', true)->assertJsonPath('unlocked_lessons', [$first->id, $second->id]);
        $this->get('/modules/'.$second->id)->assertOk();
        $this->postJson('/modules/'.$first->id.'/code-match', $input)->assertOk()->assertJsonPath('completed', true);
        $this->assertDatabaseCount('code_match_results', 1);
        $this->assertEquals(120, $user->fresh()->xp);
    }

    public function test_matching_alone_keeps_next_module_locked_until_quiz_is_correct(): void
    {
        $user = User::factory()->create();
        [$first, $second] = Lesson::turtleBasics()->learningOrder()->take(2)->get()->all();
        $this->actingAs($user)->postJson('/modules/'.$first->id.'/code-match', $this->payload())
            ->assertOk()->assertJsonPath('matching_completed', true)->assertJsonPath('completed', false);
        $this->getJson('/modules/'.$second->id)->assertForbidden();
        $answer = $first->learning_content['quiz']['answer'];
        $this->postJson('/modules/'.$first->id.'/complete', ['answer' => ($answer + 1) % 3])
            ->assertOk()->assertJsonPath('correct', false)->assertJsonPath('completed', false);
        $this->getJson('/modules/'.$second->id)->assertForbidden();
        $this->postJson('/modules/'.$first->id.'/complete', ['answer' => $answer])
            ->assertOk()->assertJsonPath('completed', true)->assertJsonPath('matching_completed', true)
            ->assertJsonPath('continue_url', '/modules/'.$second->id);
        $this->get('/modules/'.$second->id)->assertOk();
        $this->get('/modules/'.$first->id)->assertViewHas('matchingResult', fn ($result) => $result->correct_answers === 4);
        $this->assertEquals(120, $user->fresh()->xp);
    }

    public function test_existing_quiz_completions_still_need_matching_without_losing_quiz_history(): void
    {
        $user = User::factory()->create();
        [$first, $second, $third] = Lesson::turtleBasics()->learningOrder()->take(3)->get()->all();
        $user->lessons()->attach([$first->id, $second->id]);
        $this->actingAs($user)->get('/modules/'.$second->id)->assertRedirect('/modules/'.$first->id);
        $this->postJson('/modules/'.$first->id.'/code-match', $this->payload())
            ->assertOk()->assertJsonPath('completed', true);
        $this->get('/modules/'.$second->id)->assertOk();
        $this->getJson('/modules/'.$third->id)->assertForbidden();
        $this->postJson('/modules/'.$second->id.'/code-match', $this->payload())->assertOk()->assertJsonPath('completed', true);
        $this->get('/modules/'.$third->id)->assertOk();
        $this->assertSame(2, $user->lessons()->count());
    }

    public function test_feature_unlock_notice_is_returned_when_matching_completes_the_milestone(): void
    {
        $user = User::factory()->create();
        $this->completeModules($user, 3);
        $fourth = Lesson::turtleBasics()->learningOrder()->skip(3)->firstOrFail();
        $this->actingAs($user)->postJson('/modules/'.$fourth->id.'/complete', ['answer' => $fourth->learning_content['quiz']['answer']])
            ->assertOk()->assertJsonPath('unlocked_features', [])->assertJsonPath('completed', false);
        $this->getJson('/maze/1')->assertForbidden();
        $input = $this->payload();
        $this->postJson('/modules/'.$fourth->id.'/code-match', $input)->assertOk()
            ->assertJsonPath('completed', true)->assertJsonPath('unlocked_features', ['Maze Quest level 1–2'])
            ->assertJsonPath('continue_url', '/maze')->assertJsonPath('continue_label', 'Buka Maze Quest');
        $this->get('/maze/1')->assertOk();
        $this->postJson('/modules/'.$fourth->id.'/code-match', $input)->assertOk()->assertJsonPath('unlocked_features', []);
    }

    public function test_last_module_continue_link_requires_both_activities(): void
    {
        $user = User::factory()->create();
        $this->completeModules($user, 10);
        $last = Lesson::turtleBasics()->learningOrder()->get()->last();
        $this->actingAs($user);
        $assertLocked = function () use ($last) {
            $response = $this->get('/modules/'.$last->id)->assertOk();
            preg_match('/<a class="primary" role="link"[^>]*>\s*<span x-show="!lessonCompleted"/s', $response->getContent(), $match);
            $this->assertNotEmpty($match);
            $this->assertStringContainsString('aria-disabled="true"', $match[0]);
            $this->assertDoesNotMatchRegularExpression('/(?<!:)\bhref="/', $match[0]);
        };
        $assertLocked();
        $this->postJson('/modules/'.$last->id.'/code-match', $this->payload())
            ->assertOk()->assertJsonPath('completed', false)->assertJsonPath('continue_url', null);
        $assertLocked();
        $this->postJson('/modules/'.$last->id.'/complete', ['answer' => $last->learning_content['quiz']['answer']])
            ->assertOk()->assertJsonPath('completed', true)->assertJsonPath('continue_url', '/maze');
        $this->get('/modules/'.$last->id)->assertOk()->assertViewHas('isCompleted', true)
            ->assertSee('role="link"  href="'.route('maze').'"', false);
    }
}
