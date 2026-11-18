<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logic_experiments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('challenge_id');
            $table->unsignedSmallInteger('angle');
            $table->unsignedTinyInteger('distance');
            $table->timestamps();
        });
        Schema::create('logic_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('challenge_id');
            $table->timestamps();
            $table->unique(['user_id', 'challenge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logic_completions');
        Schema::dropIfExists('logic_experiments');
    }
};
