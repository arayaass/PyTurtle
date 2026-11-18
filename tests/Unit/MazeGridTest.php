<?php

namespace Tests\Unit;

use App\Services\TurtleService;
use PHPUnit\Framework\TestCase;

class MazeGridTest extends TestCase
{
    private function levels(): array
    {
        return json_decode(file_get_contents(__DIR__.'/../../resources/data/levels.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_every_grid_solution_reaches_the_final_checkpoint(): void
    {
        foreach ($this->levels() as $level) {
            $result = (new TurtleService)->run($level['solution'], $level);
            $this->assertSame('SUCCESS', $result['result']);
            $this->assertSame(count($level['checkpoints']), $result['state']['checkpoint']);
            $this->assertSame('', $level['starter']);
        }
    }

    public function test_steps_are_whole_cells_and_three_steps_finish_chapter_one(): void
    {
        $level = $this->levels()[0];
        $service = new TurtleService;
        $one = $service->run('forward(1)', $level);
        $this->assertEqualsWithDelta(150, $one['state']['x'], 0.001);
        $this->assertSame(1, $one['state']['checkpoint']);
        $this->assertSame('PARTIAL', $one['result']);
        $this->assertSame('SUCCESS', $service->run("langkah = 3\nforward(langkah)", $level)['result']);
        $this->assertSame('SUCCESS', $service->run("langkah = 1\nforward(langkah)\nforward(2)", $level)['result']);
        $this->assertSame('collision', $service->run('forward(4)', $level)['error_type']);
        $this->assertSame('ERROR', $service->run('forward(1.5)', $level)['result']);
        $this->assertSame('ERROR', $service->run('left(45)', $this->levels()[1])['result']);
    }
}
