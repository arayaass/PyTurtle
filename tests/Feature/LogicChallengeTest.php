<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogicChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_logic_feature_is_unavailable(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['/uji-logika', '/uji-logika/1'] as $url) {
            $this->get($url)->assertNotFound();
        }
        $this->postJson('/uji-logika/1/experiment', [])->assertNotFound();
        $this->postJson('/uji-logika/1/answer', [])->assertNotFound();
    }
}
