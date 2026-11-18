<?php

namespace App\Services;

use App\Models\Lesson;

class CodeMatchService
{
    public function pairs(Lesson $lesson): array
    {
        $catalog = json_decode(file_get_contents(resource_path('data/module-code-match.json')), true, 512, JSON_THROW_ON_ERROR);

        return $catalog[$lesson->curriculum_key] ?? [];
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
