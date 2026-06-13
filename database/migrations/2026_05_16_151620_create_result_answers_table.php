<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('result_id')
                ->constrained('results')
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('exam_id')
                ->constrained('exams')
                ->onDelete('cascade');

            $table->foreignId('question_id')
                ->constrained('questions')
                ->onDelete('cascade');

            // Đáp án người dùng chọn
            $table->string('user_answer')->nullable();

            // Đáp án đúng tại thời điểm làm bài
            $table->string('correct_answer');

            // Đúng / sai
            $table->boolean('is_correct')->default(false);

            // Điểm của câu này nếu cần dùng sau
            $table->float('points')->default(0);

            $table->timestamps();

            $table->index(['result_id']);
            $table->index(['user_id']);
            $table->index(['exam_id']);
            $table->index(['question_id']);
            $table->index(['is_correct']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_answers');
    }
};