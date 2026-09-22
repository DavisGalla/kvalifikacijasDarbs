<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedInteger('min_team_members')->nullable()->after('registration_mode');
            $table->unsignedInteger('max_team_members')->nullable()->after('min_team_members');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['min_team_members', 'max_team_members']);
        });
    }
};
