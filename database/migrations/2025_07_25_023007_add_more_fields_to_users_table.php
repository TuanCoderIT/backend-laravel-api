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
            $table->string('role')->default('user')->after('password');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('role');
            $table->string('phone_number')->nullable()->after('gender');
            $table->date('date_of_birth')->nullable()->after('phone_number');
            $table->string('avatar')->nullable()->after('date_of_birth');
            $table->text('bio')->nullable()->after('avatar');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('bio');
            $table->timestamp('last_login')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'gender',
                'phone_number',
                'date_of_birth',
                'avatar',
                'bio',
                'status',
                'last_login',
            ]);
        });
    }
};
