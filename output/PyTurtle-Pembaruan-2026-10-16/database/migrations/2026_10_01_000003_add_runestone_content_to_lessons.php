<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('curriculum_key')->nullable()->unique();
            $table->unsignedSmallInteger('sort_order')->nullable();
            $table->json('learning_content')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropUnique(['curriculum_key']);
            $table->dropColumn(['curriculum_key', 'sort_order', 'learning_content']);
        });
    }
};
