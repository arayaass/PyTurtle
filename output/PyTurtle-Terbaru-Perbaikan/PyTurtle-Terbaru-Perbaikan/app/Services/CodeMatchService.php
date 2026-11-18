<?php

namespace App\Services;

use App\Models\Lesson;

class CodeMatchService
{
    public function pairs(): array
    {
        return Lesson::findOrFail(4)->matching_pairs ?? [];
    }

    public function grade(array $attempts): array
    {
        $matched = [];
        $wrong = 0;
        foreach ($attempts as $a) {
            if (in_array($a['description'], $matched, true)) {
                continue;
            }
            if ($a['description'] === $a['snippet']) {
                $matched[] = $a['description'];
            } else {
                $wrong++;
            }
        }
        $correct = count($matched);

        return ['correct_answers' => $correct, 'wrong_answers' => $wrong, 'accuracy' => round($correct / max(1, $correct + $wrong) * 100, 2), 'score' => $correct * 25];
    }
}
