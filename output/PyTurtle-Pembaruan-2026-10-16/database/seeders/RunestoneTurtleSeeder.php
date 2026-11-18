<?php

namespace Database\Seeders;

use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RunestoneTurtleSeeder extends Seeder
{
    public function run(): void
    {
        $lessons = json_decode(file_get_contents(resource_path('data/turtle-lessons.json')), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($lessons) {
            foreach ($lessons as $lesson) {
                Lesson::updateOrCreate(['curriculum_key' => $lesson['key']], [
                    'title' => $lesson['title'],
                    'command' => $lesson['command'],
                    'description' => $lesson['description'],
                    'example' => $lesson['content']['examples'][0]['code'],
                    'explanation' => $lesson['content']['sections'][0]['paragraphs'][0],
                    'practice_level' => $lesson['practice_level'],
                    'sort_order' => $lesson['order'],
                    'learning_content' => $lesson['content'],
                ]);
            }
        });
    }
}
