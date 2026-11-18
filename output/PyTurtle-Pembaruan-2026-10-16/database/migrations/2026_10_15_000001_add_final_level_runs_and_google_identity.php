<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique();
        });
        Schema::table('level_progress', function (Blueprint $table) {
            $table->unsignedInteger('run_version')->default(1);
            $table->unsignedInteger('run_failures')->default(0);
            $table->boolean('run_hint_used')->default(false);
            $table->boolean('run_completed')->default(false);
            $table->timestamp('perfect_completed_at')->nullable();
        });
        // Keep existing scores. An active old run retains its penalties; old completions
        // must be replayed to earn the new badge, rather than inferring past performance.
        DB::table('level_progress')->where('level_id', 3)->update([
            'run_failures' => DB::raw('attempts'),
            'run_hint_used' => DB::raw('hints_used > 0'),
            'run_completed' => DB::raw("status = 'completed'"),
        ]);
    }

    public function down(): void
    {
        Schema::table('level_progress', fn (Blueprint $table) => $table->dropColumn([
            'run_version', 'run_failures', 'run_hint_used', 'run_completed', 'perfect_completed_at',
        ]));
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
        });
    }
};
