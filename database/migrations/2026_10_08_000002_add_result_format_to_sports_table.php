<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets each sport define its result format beyond the type (time or score): how many decimals a
 * result may have and an optional upper bound. results.value stays decimal(10,3) as the common
 * container; see App\Support\ResultFormat for how values are interpreted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            // Null means the type's default: 2 for time (hundredths), 0 for score (whole points).
            $table->unsignedTinyInteger('result_decimals')->nullable()->after('result_type');
            $table->decimal('result_max', 10, 3)->nullable()->after('result_decimals');
        });
    }

    public function down(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            $table->dropColumn(['result_decimals', 'result_max']);
        });
    }
};
