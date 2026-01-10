<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Thêm các cột metadata cho file PDF vào bảng documents
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // URL của file PDF trên Cloudinary
            $table->string('file_url')->nullable()->after('description');

            // Loại file (ví dụ: application/pdf)
            $table->string('file_type')->nullable()->after('file_url');

            // Kích thước file (bytes)
            $table->bigInteger('file_size')->nullable()->after('file_type');

            // Ảnh đại diện thumbnail
            $table->string('thumbnail')->nullable()->after('file_size');
        });
    }

    /**
     * Reverse the migrations.
     * Xóa các cột metadata khi rollback
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['file_url', 'file_type', 'file_size', 'thumbnail']);
        });
    }
};
