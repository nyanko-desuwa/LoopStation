<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Danh mục đơn vị đo (kg, g, l...). Manager quản lý; user chỉ chọn.
        Schema::create('measurement_units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)
                ->comment('Tên đơn vị hiển thị');
            $table->string('symbol', 20)
                ->comment('Ký hiệu đơn vị (kg, g, mg, l, ml, ...)');
            $table->string('category', 30)
                ->comment('weight | volume | count');
            $table->boolean('is_system')->default(true)
                ->comment('true = đơn vị gốc hệ thống; false = manager thêm');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent();

            $table->index('category');
            $table->index('is_system');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_units');
    }
};
