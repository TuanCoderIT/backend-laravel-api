<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('flashcards', function (Blueprint $table) {
            $table->id();

            $table->foreignId('flashcard_set_id')
                ->constrained('flashcard_sets')
                ->onDelete('cascade');

            // Mặt trước
            $table->text('front_text');

            // Mặt sau
            $table->text('back_text');

            // Giải thích thêm (tùy chọn)
            $table->text('explanation')->nullable();

            $table->timestamps();

            $table->index(['flashcard_set_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flashcards');
    }
};