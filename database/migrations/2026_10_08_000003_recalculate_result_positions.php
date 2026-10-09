<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Positions are derived data. Recompute them once so results saved before positions were
 * guaranteed (null or out-of-date positions) match the current ranking rules.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('results:recalculate-positions');
    }

    public function down(): void
    {
        // Nothing to undo: positions are always derivable from the result values.
    }
};
