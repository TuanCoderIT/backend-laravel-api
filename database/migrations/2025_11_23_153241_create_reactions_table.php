<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            // Đối tượng được reaction (Bài viết, Bình luận)
            $table->morphs('reactionable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Trường mới: Lưu loại reaction
            $table->string('reaction_type', 10); // Ví dụ: 'like', 'love', 'haha', 'sad', 'angry'
            $table->timestamps();
            // Đảm bảo user_id chỉ reaction 1 lần cho 1 đối tượng
            $table->unique(['reactionable_type', 'reactionable_id', 'user_id']);
            // Tạo index để tối ưu truy vấn theo loại reaction
            $table->index(['reactionable_type', 'reactionable_id', 'reaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
