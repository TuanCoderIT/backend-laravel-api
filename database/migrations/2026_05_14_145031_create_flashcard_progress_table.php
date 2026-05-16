<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('flashcard_progress', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('flashcard_id')
                ->constrained('flashcards')
                ->onDelete('cascade');

            // new | learning | mastered
            $table->enum('status', [
                'new',
                'learning',
                'mastered',
            ])->default('new');

            // Số lần trả lời đúng
            $table->unsignedInteger('correct_count')->default(0);

            // Tổng số lần ôn tập
            $table->unsignedInteger('review_count')->default(0);

            $table->timestamp('last_reviewed_at')->nullable();

            // Phục vụ spaced repetition sau này
            $table->timestamp('next_review_at')->nullable();

            $table->timestamps();

            // Một user chỉ có một progress cho mỗi flashcard
            $table->unique(['user_id', 'flashcard_id']);

            $table->index(['status']);
            $table->index(['next_review_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flashcard_progress');
    }
};