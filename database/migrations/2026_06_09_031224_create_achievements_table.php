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
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->string('icon')->nullable();
            // emoji hoặc tên icon: trophy, flame, star

            $table->string('type')->default('general');
            // quiz, flashcard, streak, xp, creator, community, leaderboard

            $table->string('rarity')->default('common');
            // common, rare, epic, legendary

            $table->unsignedInteger('target_value')->default(1);

            $table->unsignedInteger('xp_reward')->default(0);
            $table->unsignedInteger('token_reward')->default(0);

            $table->string('reward_title')->nullable();
            // Ví dụ: Quiz Master, Flashcard Pro

            $table->string('reward_trophy')->nullable();
            // Ví dụ: bronze, silver, gold

            $table->json('conditions')->nullable();
            // Ví dụ: {"quiz_score": 80} hoặc {"streak_length": 7}

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
