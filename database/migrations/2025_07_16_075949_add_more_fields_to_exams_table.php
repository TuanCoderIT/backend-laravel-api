<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->integer('passing_score')->default(70);
            $table->integer('max_attempts')->default(3);
            $table->json('learning_objectives')->nullable();
            $table->json('prerequisites')->nullable();
            $table->json('tags')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['passing_score', 'max_attempts', 'learning_objectives', 'prerequisites', 'tags']);
        });
    }
};
