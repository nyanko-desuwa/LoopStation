<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sự kiện Ngày hội sống xanh. Manager tạo (ghi SYSTEM_LOGS), cơ sở suy ra từ facility manager.
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('location', 300)
                ->comment('Địa điểm tổ chức bên ngoài (trường học, công viên...)');
            $table->string('qr_code', 100)->unique()
                ->comment('Mã QR định danh; chỉ active trong khung giờ sự kiện');
            $table->string('image_url', 500)->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->timestamp('expired_at')->nullable()
                ->comment('Mốc hết hạn đăng ký/auto-clean');
            $table->enum('status', ['upcoming', 'active', 'ended', 'cancelled'])
                ->default('upcoming');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'start_time']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
