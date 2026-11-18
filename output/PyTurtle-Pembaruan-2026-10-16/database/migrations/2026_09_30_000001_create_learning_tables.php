<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('display_name')->nullable();
            $t->unsignedInteger('xp')->default(0);
            $t->unsignedTinyInteger('current_level')->default(1);
            $t->unsignedInteger('adventure_version')->default(1);
        });
        Schema::create('levels', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->json('definition');
            $t->timestamps();
        });
        Schema::create('lessons', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('command');
            $t->text('description');
            $t->text('example');
            $t->text('explanation');
            $t->unsignedTinyInteger('practice_level');
            $t->timestamps();
        });
        Schema::create('game_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('current_level')->default(1);
            $t->unsignedTinyInteger('current_checkpoint')->default(0);
            $t->double('player_x')->default(90);
            $t->double('player_y')->default(270);
            $t->double('player_direction')->default(0);
            $t->string('story_state')->default('intro');
            $t->timestamps();
        });
        Schema::create('level_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('level_id')->constrained()->cascadeOnDelete();
            $t->string('status')->default('in_progress');
            $t->unsignedSmallInteger('score')->default(0);
            $t->unsignedSmallInteger('best_score')->default(0);
            $t->unsignedInteger('attempts')->default(0);
            $t->unsignedSmallInteger('hints_used')->default(0);
            $t->text('code')->nullable();
            $t->json('state')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'level_id']);
        });
        Schema::create('code_submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('level_id')->constrained();
            $t->uuid('request_id');
            $t->text('code');
            $t->string('result');
            $t->unsignedSmallInteger('score')->default(0);
            $t->string('error_type')->nullable();
            $t->json('feedback');
            $t->timestamps();
            $t->unique(['user_id', 'request_id']);
        });
        Schema::create('code_match_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('lesson_id')->constrained();
            $t->uuid('request_id');
            $t->unsignedSmallInteger('correct_answers');
            $t->unsignedSmallInteger('wrong_answers');
            $t->decimal('accuracy', 5, 2);
            $t->unsignedSmallInteger('score');
            $t->timestamp('completed_at');
            $t->timestamps();
            $t->unique(['user_id', 'request_id']);
        });
        Schema::create('user_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->boolean('music_enabled')->default(false);
            $t->boolean('sound_enabled')->default(true);
            $t->boolean('reduced_motion')->default(false);
            $t->timestamps();
        });
        Schema::create('lesson_user', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        foreach (['lesson_user', 'user_settings', 'code_match_results', 'code_submissions', 'level_progress', 'game_progress', 'lessons', 'levels'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['display_name', 'xp', 'current_level', 'adventure_version']));
    }
};
