<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('exercises');
            $table->timestamps();
        });

        Schema::table('workout_logs', function (Blueprint $table) {
            // Which program day the entry was logged under (kept as text so it survives day edits/deletes).
            $table->string('day_name')->nullable()->after('exercise');
        });
    }

    public function down(): void
    {
        Schema::table('workout_logs', function (Blueprint $table) {
            $table->dropColumn('day_name');
        });

        Schema::dropIfExists('workout_days');
    }
};
