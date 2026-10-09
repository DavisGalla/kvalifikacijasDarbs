<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Records how a competition's winner was decided: 'automatic' (taken from the ranking and kept in
 * sync with it) or 'manual' (chosen by the organizer, with the reason in winner_note).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('winner_method', 16)->nullable()->after('winner_id');
            $table->text('winner_note')->nullable()->after('winner_method');
        });

        // Winners saved before this distinction existed were all picked by hand.
        DB::table('competitions')->whereNotNull('winner_id')->update([
            'winner_method' => 'manual',
            'winner_note' => 'Recorded before winners were linked to results.',
        ]);
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['winner_method', 'winner_note']);
        });
    }
};
