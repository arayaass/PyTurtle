<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', fn (Blueprint $t) => $t->json('matching_pairs')->nullable());
    }

    public function down(): void
    {
        Schema::table('lessons', fn (Blueprint $t) => $t->dropColumn('matching_pairs'));
    }
};
