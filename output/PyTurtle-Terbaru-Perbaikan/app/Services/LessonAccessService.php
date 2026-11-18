<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\User;

class LessonAccessService
{
    /** A lesson opens only when every earlier lesson has been completed. */
    public function unlockedIds(array $orderedIds, array $completedIds): array
    {
        $completed = array_fill_keys(array_map('intval', $completedIds), true);
        $unlocked = [];
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            $unlocked[] = $id;
            if (! isset($completed[$id])) {
                break;
            }
        }

        return $unlocked;
    }

    public function forUser(User $user): array
    {
        $lessons = Lesson::turtleBasics()->learningOrder()->get();
        $done = array_map('intval', $user->lessons()->turtleBasics()->pluck('lessons.id')->all());

        return [
            'lessons' => $lessons,
            'done' => $done,
            'unlocked' => $this->unlockedIds($lessons->pluck('id')->all(), $done),
            'requiredLesson' => $lessons->first(fn (Lesson $lesson) => ! in_array($lesson->id, $done, true)),
        ];
    }

    public function lockMessage(?Lesson $requiredLesson): string
    {
        return $requiredLesson
            ? 'Materi masih terkunci. Selesaikan materi '.sprintf('%02d', $requiredLesson->sort_order).' — '.$requiredLesson->title.' terlebih dahulu.'
            : 'Materi masih terkunci. Selesaikan materi sebelumnya terlebih dahulu.';
    }

    public function assertAccessible(User $user, Lesson $module): void
    {
        $progress = $this->forUser($user);
        // The older lessons used by Code Matching keep their existing behavior.
        if ($progress['lessons']->contains('id', $module->id)) {
            abort_unless(in_array($module->id, $progress['unlocked'], true), 403, $this->lockMessage($progress['requiredLesson']));
        }
    }
}
