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
        Schema::create('token_pricings', function (Blueprint $table) {
            $table->id();
            $table->string('target_type'); // 'quiz', 'docs', 'course',...
            $table->unsignedBigInteger('target_id');
            $table->integer('price_token');
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_pricings');
    }
};
