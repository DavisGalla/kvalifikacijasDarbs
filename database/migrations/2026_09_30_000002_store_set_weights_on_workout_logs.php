<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_logs', function (Blueprint $table) {
            $table->json('set_weights')->nullable()->after('exercise');
        });

        // Existing entries used one weight for every set.
        DB::table('workout_logs')->orderBy('id')->each(function ($log) {
            DB::table('workout_logs')->where('id', $log->id)->update([
                'set_weights' => json_encode(array_fill(0, max(1, (int) $log->sets), (float) $log->weight)),
            ]);
        });

        Schema::table('workout_logs', function (Blueprint $table) {
            $table->dropColumn(['weight', 'sets']);
        });
    }

    public function down(): void
    {
        Schema::table('workout_logs', function (Blueprint $table) {
            $table->decimal('weight', 6, 2)->default(0);
            $table->unsignedSmallInteger('sets')->default(1);
        });

        DB::table('workout_logs')->orderBy('id')->each(function ($log) {
            $weights = json_decode($log->set_weights, true) ?: [0];
            DB::table('workout_logs')->where('id', $log->id)->update([
                'weight' => max($weights),
                'sets' => count($weights),
            ]);
        });

        Schema::table('workout_logs', function (Blueprint $table) {
            $table->dropColumn('set_weights');
        });
    }
};
