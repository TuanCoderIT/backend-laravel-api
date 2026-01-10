<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Nội dung bài viết
            $table->text('content')->nullable();

            // Đính kèm (ảnh, file, video…) lưu dạng JSON nếu không dùng Spatie
            $table->json('attachments')->nullable();

            // Liên kết đến Course, Document, Quiz, Group, ...
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();

            // Nếu bài viết thuộc về nhóm nào đó
            $table->foreignId('group_id')->nullable()->constrained('groups')->cascadeOnDelete();

            // Ghim bài viết
            $table->boolean('is_pinned')->default(false);

            // Public / private / group_only
            $table->string('visibility')->default('public');

            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
