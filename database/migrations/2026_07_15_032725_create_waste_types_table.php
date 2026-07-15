<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Danh mục loại rác. is_system=true = chuẩn hệ thống; false = user tự thêm (chỉ họ thấy).
        Schema::create('waste_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)
                ->comment('Tên loại rác');
            $table->string('icon', 50)->nullable()
                ->comment('Icon/emoji hiển thị (tùy chọn)');
            $table->boolean('is_system')->default(true)
                ->comment('true = phân loại gốc; false = user tự thêm');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent();

            $table->index('is_system');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_types');
    }
};
