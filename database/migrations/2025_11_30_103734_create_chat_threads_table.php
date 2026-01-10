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
        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // direct, group, course, community_group,...
            $table->string('name')->nullable(); // tên room nếu là group
            $table->unsignedBigInteger('owner_id')->nullable(); // nếu cần
            $table->unsignedBigInteger('group_id')->nullable(); // nối với bảng groups community
            $table->unsignedBigInteger('course_id')->nullable(); // nếu sau này có chat theo course
            $table->timestamps();

            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_threads');
    }
};
