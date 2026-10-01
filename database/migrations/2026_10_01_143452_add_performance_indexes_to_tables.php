<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SQLite (the app's default connection) does not auto-index
     * foreignId columns the way MySQL's InnoDB does, so columns that
     * are filtered on directly need an explicit index.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->index(['captain_id', 'sport_id']);
        });

        Schema::table('team_members', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('team_invitations', function (Blueprint $table) {
            $table->index('invited_user_id');
        });

        Schema::table('personal_bests', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('personal_best_entries', function (Blueprint $table) {
            $table->index('personal_best_id');
        });

        Schema::table('workout_days', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index('post_id');
        });

        Schema::table('matchups', function (Blueprint $table) {
            $table->index('competition_id');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->index(['status', 'end_time', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex(['captain_id', 'sport_id']);
        });

        Schema::table('team_members', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('team_invitations', function (Blueprint $table) {
            $table->dropIndex(['invited_user_id']);
        });

        Schema::table('personal_bests', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('personal_best_entries', function (Blueprint $table) {
            $table->dropIndex(['personal_best_id']);
        });

        Schema::table('workout_days', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['post_id']);
        });

        Schema::table('matchups', function (Blueprint $table) {
            $table->dropIndex(['competition_id']);
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropIndex(['status', 'end_time', 'start_time']);
        });
    }
};
