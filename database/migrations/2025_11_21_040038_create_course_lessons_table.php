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
        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('course_chapters')->onDelete('cascade');

            $table->string('title');
            $table->text('content')->nullable(); // dùng cho bài text

            // loại bài học
            $table->enum('type', ['video', 'text'])->default('text');

            // bài video
            $table->string('video_url')->nullable();

            // thời lượng bài học (giây)
            $table->integer('duration_seconds')->default(0);

            // học thử
            $table->boolean('is_free_preview')->default(false);

            $table->integer('order')->default(0);

            $table->index(['chapter_id', 'order']);
            $table->index(['type']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_lessons');
    }
};
