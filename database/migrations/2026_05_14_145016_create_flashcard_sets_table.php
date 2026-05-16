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

            // manual | quiz_wrong_answers | ai_generated
            $table->enum('source_type', [
                'manual',
                'quiz_wrong_answers',
                'ai_generated',
            ])->default('manual');

            $table->boolean('is_ai_generated')->default(false);

            $table->enum('status', [
                'draft',
                'pending',
                'published',
                'rejected',
                'archived',
            ])->default('draft');

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();

            // Nếu bộ thẻ được tạo từ một quiz cụ thể
            $table->foreignId('exam_id')
                ->nullable()
                ->constrained('exams')
                ->nullOnDelete();

            // Màu dùng để hiển thị UI
            $table->string('color', 20)
                ->nullable();

            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['category_id']);
            $table->index(['source_type']);
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
