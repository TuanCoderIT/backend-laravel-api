<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->text('content');

            $table->enum('status', ['active', 'inactive', 'deleted'])->default('active');

            // reply dạng cây
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('post_comments')->cascadeOnDelete();

            $table->timestamps();
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_comments');
    }
};
