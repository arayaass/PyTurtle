<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MazeGridSeeder extends Seeder
{
    public function run(): void
    {
        $levels = json_decode(file_get_contents(resource_path('data/levels.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($levels) {
            foreach ($levels as $level) {
                Level::updateOrCreate(['id' => $level['id']], [
                    'title' => $level['title'],
                    'definition' => $level,
                ]);
            }
        });
    }
}
