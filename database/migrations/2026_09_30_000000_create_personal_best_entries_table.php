<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_best_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_best_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight', 6, 2);
            $table->timestamps();
        });

        // Seed the history with each existing PB's current value.
        DB::table('personal_bests')->orderBy('id')->each(function ($pb) {
            DB::table('personal_best_entries')->insert([
                'personal_best_id' => $pb->id,
                'weight' => $pb->weight,
                'created_at' => $pb->updated_at,
                'updated_at' => $pb->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_best_entries');
    }
};
