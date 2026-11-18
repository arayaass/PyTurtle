<?php

namespace Tests;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function completeModules(User $user, int $count = 11): void
    {
        $lessons = Lesson::turtleBasics()->learningOrder()->limit($count)->get();
        $user->lessons()->syncWithoutDetaching($lessons->pluck('id')->all());
        foreach ($lessons as $lesson) {
            $this->completeMatching($user, $lesson);
        }
    }

    protected function completeMatching(User $user, Lesson $lesson): void
    {
        // Zero-score fixture isolates the XP awarded by the behavior under test.
        $user->codeMatchResults()->firstOrCreate(['lesson_id' => $lesson->id], [
            'request_id' => (string) Str::uuid(),
            'correct_answers' => 4, 'wrong_answers' => 0, 'accuracy' => 100,
            'score' => 0, 'completed_at' => now(),
        ]);
    }
}
