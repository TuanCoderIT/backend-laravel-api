<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('xp_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->integer('amount');

            $table->string('source_type');
            // quiz_completed, flashcard_review, wrong_answer_flashcards, achievement, leaderboard

            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('description')->nullable();

            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['source_type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xp_logs');
    }
};
