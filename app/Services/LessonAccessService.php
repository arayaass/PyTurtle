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
        $quizDone = array_map('intval', $user->lessons()->turtleBasics()->pluck('lessons.id')->all());
        $matchingDone = array_map('intval', $user->codeMatchResults()->where('correct_answers', 4)
            ->whereNotNull('completed_at')->distinct()->pluck('lesson_id')->all());
        $done = array_values(array_intersect($quizDone, $matchingDone));

        return [
            'lessons' => $lessons,
            'done' => $done,
            'quizDone' => $quizDone,
            'matchingDone' => $matchingDone,
            'unlocked' => $this->unlockedIds($lessons->pluck('id')->all(), $done),
            'requiredLesson' => $lessons->first(fn (Lesson $lesson) => ! in_array($lesson->id, $done, true)),
        ];
    }

    public function completionState(User $user, Lesson $lesson): array
    {
        $progress = $this->forUser($user);
        $legacyCompleted = ! $progress['lessons']->contains('id', $lesson->id)
            && $user->lessons()->where('lessons.id', $lesson->id)->exists();
        $completed = $legacyCompleted || in_array($lesson->id, $progress['done'], true);
        $position = $progress['lessons']->search(fn (Lesson $item) => $item->id === $lesson->id);
        $next = $position !== false ? $progress['lessons']->get($position + 1) : null;

        return [
            'lesson_id' => $lesson->id,
            'completed' => $completed,
            'quiz_passed' => $legacyCompleted || in_array($lesson->id, $progress['quizDone'], true),
            'matching_completed' => in_array($lesson->id, $progress['matchingDone'], true),
            'completed_lessons' => count($progress['done']),
            'unlocked_lessons' => $progress['unlocked'],
            'continue_url' => $completed ? ($next ? route('modules.show', $next, false) : route('maze', [], false)) : null,
            'continue_label' => $completed ? ($next ? 'Lanjut ke modul berikutnya' : 'Buka Maze Quest') : null,
        ];
    }

    public function completedCount(User $user): int
    {
        $progress = $this->forUser($user);
        $count = 0;
        foreach ($progress['lessons'] as $lesson) {
            if (! in_array($lesson->id, $progress['done'], true)) {
                break;
            }
            $count++;
        }

        return $count;
    }

    public function requiredForMaze(int $level): int
    {
        return $level <= 2 ? 4 : ($level === 3 ? 8 : 11);
    }

    public function mazeAllowed(User $user, int $level): bool
    {
        return $level >= 1 && $level <= 5 && $this->completedCount($user) >= $this->requiredForMaze($level);
    }

    public function mazeMessage(int $level): string
    {
        return 'Level Maze Quest '.$level.' masih terkunci. Selesaikan latihan memasangkan kode dan jawab pertanyaan pada modul sampai modul '.$this->requiredForMaze($level).' terlebih dahulu.';
    }

    public function newlyUnlocked(int $before, int $after): array
    {
        $features = [];
        foreach ([4 => ['Maze Quest level 1–2'], 8 => ['Maze Quest level 3'], 11 => ['Maze Quest level 4–5']] as $threshold => $names) {
            if ($before < $threshold && $after >= $threshold) {
                $features = array_merge($features, $names);
            }
        }

        return $features;
    }

    public function lockMessage(?Lesson $requiredLesson): string
    {
        return $requiredLesson
            ? 'Materi masih terkunci. Selesaikan latihan memasangkan kode dan jawab pertanyaan pada materi '.sprintf('%02d', $requiredLesson->sort_order).' — '.$requiredLesson->title.' terlebih dahulu.'
            : 'Materi masih terkunci. Selesaikan materi sebelumnya terlebih dahulu.';
    }

    public function assertAccessible(User $user, Lesson $module): void
    {
        $progress = $this->forUser($user);
        // Legacy lessons keep their existing access behavior.
        if ($progress['lessons']->contains('id', $module->id)) {
            abort_unless(in_array($module->id, $progress['unlocked'], true), 403, $this->lockMessage($progress['requiredLesson']));
        }
    }
}
