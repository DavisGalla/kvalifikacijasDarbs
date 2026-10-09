<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Matchups are identified by their round: within a round a team plays at most once, so a repeated
 * submission cannot create a second copy of the same game, while the same two teams can still
 * meet again in a later round.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matchups', function (Blueprint $table) {
            $table->unsignedSmallInteger('round')->default(1)->after('competition_id');
            $table->date('played_on')->nullable()->after('round');
        });

        // Existing matchups had no round: place each one, in the order it was recorded, in the
        // first round where neither of its teams has played yet.
        $rounds = [];

        foreach (DB::table('matchups')->orderBy('id')->cursor() as $matchup) {
            $round = 1;

            while (isset($rounds[$matchup->competition_id][$round][$matchup->home_team_id])
                || isset($rounds[$matchup->competition_id][$round][$matchup->away_team_id])) {
                $round++;
            }

            $rounds[$matchup->competition_id][$round][$matchup->home_team_id] = true;
            $rounds[$matchup->competition_id][$round][$matchup->away_team_id] = true;

            DB::table('matchups')->where('id', $matchup->id)->update(['round' => $round]);
        }

        Schema::table('matchups', function (Blueprint $table) {
            $table->unique(['competition_id', 'round', 'home_team_id']);
            $table->unique(['competition_id', 'round', 'away_team_id']);
        });
    }

    public function down(): void
    {
        Schema::table('matchups', function (Blueprint $table) {
            $table->dropUnique(['competition_id', 'round', 'home_team_id']);
            $table->dropUnique(['competition_id', 'round', 'away_team_id']);
            $table->dropColumn(['round', 'played_on']);
        });
    }
};
