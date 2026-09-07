<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->enum('registrant_type', ['user', 'team']);
            $table->unsignedBigInteger('registrant_id');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamp('registered_at');
            $table->unique(['competition_id', 'registrant_type', 'registrant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
