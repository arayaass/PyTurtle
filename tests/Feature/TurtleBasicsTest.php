<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TurtleBasicsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeding_twice_keeps_lesson_ids_and_existing_completion(): void
    {
        $user = User::factory()->create();
        $first = Lesson::turtleBasics()->learningOrder()->firstOrFail();
        $user->lessons()->attach($first->id);
        $ids = Lesson::turtleBasics()->learningOrder()->pluck('id')->all();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($ids, Lesson::turtleBasics()->learningOrder()->pluck('id')->all());
        $this->assertSame(11, Lesson::turtleBasics()->count());
        $this->assertTrue($user->lessons()->where('lessons.id', $first->id)->exists());
        $this->assertSame('Belok ke kanan', Lesson::findOrFail(4)->title);
        $this->assertCount(4, Lesson::findOrFail(4)->matching_pairs);
    }

    public function test_all_eleven_reading_pages_open_in_order(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/modules')->assertOk()->assertSeeText('11 materi');

        foreach (Lesson::turtleBasics()->learningOrder()->get() as $lesson) {
            $this->completeMatching($user, $lesson);
            $this->get('/modules/'.$lesson->id)
                ->assertOk()
                ->assertSeeText($lesson->title)
                ->assertSeeText($lesson->learning_content['quiz']['question'])
                ->assertSee($lesson->learning_content['source_url']);
            $this->postJson('/modules/'.$lesson->id.'/complete', ['answer' => $lesson->learning_content['quiz']['answer']])
                ->assertOk()->assertJsonPath('correct', true);
        }

        $this->get('/modules/4')->assertOk()->assertSeeText('Belok ke kanan');
    }

    public function test_wrong_answer_does_not_complete_a_lesson_or_award_xp(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::turtleBasics()->firstOrFail();
        $wrong = ($lesson->learning_content['quiz']['answer'] + 1) % 3;

        $this->actingAs($user)->from('/modules/'.$lesson->id)
            ->post('/modules/'.$lesson->id.'/complete', ['answer' => $wrong])
            ->assertRedirect('/modules/'.$lesson->id.'#turtle-quiz')
            ->assertSessionHasErrors('answer');

        $this->assertDatabaseMissing('lesson_user', ['user_id' => $user->id, 'lesson_id' => $lesson->id]);
        $this->assertSame(0, $user->fresh()->xp);
    }

    public function test_a_correct_answer_awards_twenty_xp_only_once(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::turtleBasics()->firstOrFail();
        $this->completeMatching($user, $lesson);
        $answer = $lesson->learning_content['quiz']['answer'];
        $this->actingAs($user);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->from('/modules/'.$lesson->id)
                ->post('/modules/'.$lesson->id.'/complete', ['answer' => (string) $answer])
                ->assertRedirect('/modules/'.$lesson->id.'#turtle-quiz')
                ->assertSessionHasNoErrors();
            $this->assertSame(20, $user->fresh()->xp);
        }

        $this->assertSame(1, $user->lessons()->where('lessons.id', $lesson->id)->count());
        $this->get('/modules/'.$lesson->id)->assertOk()->assertSeeText('Kamu sudah menyelesaikan materi ini.');
    }

    public function test_json_wrong_answer_returns_feedback_without_a_redirect_or_xp(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::turtleBasics()->firstOrFail();
        $wrong = ($lesson->learning_content['quiz']['answer'] + 1) % 3;
        $this->actingAs($user)->postJson('/modules/'.$lesson->id.'/complete', ['answer' => $wrong])
            ->assertOk()->assertJsonPath('correct', false)->assertJsonPath('completed', false)
            ->assertJsonPath('xp_awarded', 0)->assertJsonPath('message', $lesson->learning_content['quiz']['explanation'])
            ->assertSessionHasNoErrors()->assertSessionMissing('status');
        $this->assertSame(0, $user->fresh()->xp);
        $this->assertDatabaseMissing('lesson_user', ['user_id' => $user->id, 'lesson_id' => $lesson->id]);
    }

    public function test_json_completion_awards_xp_once_and_can_be_checked_again(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::turtleBasics()->firstOrFail();
        $this->completeMatching($user, $lesson);
        $answer = $lesson->learning_content['quiz']['answer'];
        $this->actingAs($user);
        foreach ([20, 0] as $reward) {
            $this->postJson('/modules/'.$lesson->id.'/complete', ['answer' => $answer])
                ->assertOk()->assertJsonPath('correct', true)->assertJsonPath('completed', true)
                ->assertJsonPath('xp_awarded', $reward)->assertJsonPath('completed_lessons', 1)
                ->assertSessionMissing('status');
            $this->assertSame(20, $user->fresh()->xp);
        }
        $this->postJson('/modules/'.$lesson->id.'/complete', ['answer' => ($answer + 1) % 3])
            ->assertOk()->assertJsonPath('correct', false)->assertJsonPath('completed', true);
        $this->assertSame(20, $user->fresh()->xp);
    }

    public function test_json_invalid_answer_is_rejected_without_completion(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::turtleBasics()->firstOrFail();
        foreach ([[], ['answer' => 999], ['answer' => 'bad']] as $input) {
            $this->actingAs($user)->postJson('/modules/'.$lesson->id.'/complete', $input)
                ->assertUnprocessable()->assertJsonValidationErrors('answer');
        }
        $this->assertSame(0, $user->fresh()->xp);
        $this->assertDatabaseMissing('lesson_user', ['user_id' => $user->id, 'lesson_id' => $lesson->id]);
    }

    public function test_locked_lesson_cannot_be_opened_or_completed_directly(): void
    {
        $user = User::factory()->create();
        $lessons = Lesson::turtleBasics()->learningOrder()->get();
        $first = $lessons[0];
        $this->actingAs($user);
        foreach ($lessons->skip(1) as $locked) {
            $this->get('/modules/'.$locked->id)->assertRedirect('/modules/'.$first->id)
                ->assertSessionHas('lesson_locked');
            $this->getJson('/modules/'.$locked->id)->assertForbidden();
            $this->postJson('/modules/'.$locked->id.'/complete', ['answer' => $locked->learning_content['quiz']['answer']])
                ->assertForbidden();
            $this->assertDatabaseMissing('lesson_user', ['user_id' => $user->id, 'lesson_id' => $locked->id]);
        }
        $this->assertSame(0, $user->fresh()->xp);
    }

    public function test_correct_answer_unlocks_only_the_next_lesson(): void
    {
        $user = User::factory()->create();
        $lessons = Lesson::turtleBasics()->learningOrder()->get();
        $this->actingAs($user);
        $this->getJson('/modules/'.$lessons[1]->id)->assertForbidden();
        $this->completeMatching($user, $lessons[0]);
        $this->postJson('/modules/'.$lessons[0]->id.'/complete', ['answer' => $lessons[0]->learning_content['quiz']['answer']])
            ->assertOk()->assertJsonPath('unlocked_lessons', [$lessons[0]->id, $lessons[1]->id]);
        $this->get('/modules/'.$lessons[1]->id)->assertOk();
        $this->getJson('/modules/'.$lessons[2]->id)->assertForbidden();
        $this->get('/modules/'.$lessons[0]->id)->assertOk();
    }

    public function test_existing_later_completions_do_not_bypass_a_missing_earlier_lesson(): void
    {
        $user = User::factory()->create();
        $lessons = Lesson::turtleBasics()->learningOrder()->get();
        $user->lessons()->attach([$lessons[1]->id, $lessons[2]->id]);
        $this->completeMatching($user, $lessons[1]);
        $this->completeMatching($user, $lessons[2]);
        $this->actingAs($user)->getJson('/modules/'.$lessons[2]->id)->assertForbidden();
        $this->postJson('/modules/'.$lessons[2]->id.'/complete', ['answer' => $lessons[2]->learning_content['quiz']['answer']])
            ->assertForbidden();
        $this->assertSame(2, $user->lessons()->count());
        $this->completeMatching($user, $lessons[0]);
        $this->postJson('/modules/'.$lessons[0]->id.'/complete', ['answer' => $lessons[0]->learning_content['quiz']['answer']])
            ->assertOk()->assertJsonPath('unlocked_lessons', $lessons->take(4)->pluck('id')->all());
        $this->get('/modules/'.$lessons[2]->id)->assertOk();
    }
}
