<?php

namespace App\Services;

use App\Models\LevelProgress;

class FinalLevelRewardService
{
    public function score(LevelProgress $progress): int
    {
        return max(60, 100 - min(40, $progress->run_failures * 5 + ($progress->run_hint_used ? 5 : 0)));
    }

    public function perfect(LevelProgress $progress): bool
    {
        return $progress->run_failures === 0 && ! $progress->run_hint_used;
    }
}
