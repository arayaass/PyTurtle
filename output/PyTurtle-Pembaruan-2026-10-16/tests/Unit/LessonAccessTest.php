<?php

namespace Tests\Unit;

use App\Services\LessonAccessService;
use PHPUnit\Framework\TestCase;

class LessonAccessTest extends TestCase
{
    public function test_only_completed_prefix_and_next_lesson_are_open(): void
    {
        $service = new LessonAccessService;
        $ids = [7, 12, 18, 24];
        foreach ([
            [[], [7]],
            [[7], [7, 12]],
            [[7, 12], [7, 12, 18]],
            [[7, 12, 18], $ids],
            [$ids, $ids],
            [[12, 18, 24], [7]],
            [[7, 18, 24], [7, 12]],
            [[4, 999], [7]],
            [['7', '12'], [7, 12, 18]],
        ] as [$done, $expected]) {
            $this->assertSame($expected, $service->unlockedIds($ids, $done));
        }
        $this->assertSame([], $service->unlockedIds([], []));
    }
}
