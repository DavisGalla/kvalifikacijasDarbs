<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('exercise');
            $table->decimal('weight', 6, 2);
            $table->unsignedSmallInteger('sets');
            $table->date('performed_on');
            $table->timestamps();

            $table->index(['user_id', 'performed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_logs');
    }
};
