<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\User;
use Database\Seeders\MazeGridSeeder;
use Database\Seeders\RunestoneTurtleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MazeGridFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_grid_maps_preserves_account_and_best_score(): void
    {
        $this->withoutVite();
        $this->seed(MazeGridSeeder::class);
        $user = User::factory()->create(['xp' => 95]);
        $this->seed(RunestoneTurtleSeeder::class);
        $this->completeModules($user, 4);
        $progress = $user->levelProgress()->create([
            'level_id' => 1,
            'status' => 'completed',
            'best_score' => 95,
            'code' => 'forward(180)',
        ]);
        $this->seed(MazeGridSeeder::class);
        $this->assertSame(5, Level::count());
        $this->assertSame(95, $user->fresh()->xp);
        $this->assertSame(95, $progress->fresh()->best_score);
        $this->assertSame('completed', $progress->fresh()->status);
        $this->actingAs($user)->get('/maze/1')->assertOk()
            ->assertViewHas('definition', fn ($level) => $level['starter'] === '' && $level['movement_unit'] === 'grid')
            ->assertSeeText('1 kotak = 1 langkah');
    }
}
