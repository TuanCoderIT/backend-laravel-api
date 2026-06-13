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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('xp')->default(0)->after('password');

            $table->unsignedInteger('current_streak')->default(0)->after('xp');

            $table->unsignedInteger('longest_streak')->default(0)->after('current_streak');

            $table->timestamp('last_activity_at')->nullable()->after('longest_streak');

            $table->unsignedInteger('streak_freezes')->default(0)->after('longest_streak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('xp');
            $table->dropColumn('current_streak');
            $table->dropColumn('longest_streak');
            $table->dropColumn('last_activity_at');
            $table->dropColumn('streak_freezes');
        });
    }
};
