<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['group_id', 'created_at'], 'posts_group_id_created_at_index');
            $table->index(['created_at'], 'posts_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_group_id_created_at_index');
            $table->dropIndex('posts_created_at_index');
        });
    }
};
