<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->enum('registrant_type', ['user', 'team']);
            $table->unsignedBigInteger('registrant_id');
            $table->decimal('value', 10, 3);
            $table->unsignedInteger('position')->nullable();
            $table->timestamps();
            $table->unique(['competition_id', 'registrant_type', 'registrant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
