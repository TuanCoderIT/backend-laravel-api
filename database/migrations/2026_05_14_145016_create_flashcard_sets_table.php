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
        Schema::create('flashcard_sets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('visibility', [
                'private',
                'public',
            ])->default('private');

            $table->enum('source_type', [
                'manual',
                'quiz_wrong_answers',
                'ai_generated',
            ])->default('manual');

            $table->enum('status', [
                'draft',
                'published',
                'archived',
            ])->default('published');

            // Nếu bộ thẻ được tạo từ một quiz cụ thể
            $table->foreignId('exam_id')
                ->nullable()
                ->constrained('exams')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['category_id']);
            $table->index(['source_type']);
            $table->index(['visibility', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flashcard_sets');
    }
};
