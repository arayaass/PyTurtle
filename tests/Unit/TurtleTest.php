<?php

namespace Tests\Unit;

use App\Services\TurtleService;
use PHPUnit\Framework\TestCase;

class TurtleTest extends TestCase
{
    private function levels(): array
    {
        return json_decode(file_get_contents(__DIR__.'/../../resources/data/levels.json'), true);
    }

    public function test_all_authored_solutions_reach_goal(): void
    {
        foreach ($this->levels() as $level) {
            $this->assertSame('SUCCESS', (new TurtleService)->run($level['solution'], $level)['result']);
        }
    }

    public function test_equivalent_programs_and_prefixes_work(): void
    {
        $level = $this->levels()[0];
        foreach (['turtle.forward( 3 ) # comment', "forward(2)\nbackward(1)\nforward(2)", "forward(1)\nforward(1)\nforward(1)"] as $code) {
            $this->assertSame('SUCCESS', (new TurtleService)->run("langkah = 0\nforward(langkah)\n".$code, $level)['result']);
        }
    }

    public function test_unsafe_invalid_and_unbounded_commands_are_rejected(): void
    {
        $level = $this->levels()[2];
        foreach (['import os', 'eval(1)', 'open("file")', 'forward 100', 'forward()', 'penup(1)', 'forward(1001)', 'forward(NaN)', 'forward(1);right(90)', str_repeat("left(1)\n", 101)] as $code) {
            $this->assertSame('ERROR', (new TurtleService)->run("langkah = 0\nforward(langkah)\n".$code, $level)['result'], $code);
        }
    }

    public function test_collision_prevents_tunneling_and_penup_does_not_bypass_it(): void
    {
        $result = (new TurtleService)->run("penup()\nforward(1000)", $this->levels()[2]);
        $this->assertSame('PARTIAL', $result['result']);
        $this->assertSame('collision', $result['error_type']);
        $this->assertLessThan(235, $result['state']['x']);
    }

    public function test_later_error_prevents_partial_execution(): void
    {
        $result = (new TurtleService)->run("forward(100)\nimport os", $this->levels()[0]);
        $this->assertSame(90, $result['state']['x']);
        $this->assertSame(2, $result['line']);
    }
}
