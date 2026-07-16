<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Danh mục quà đổi bằng điểm ví (khác EVENT_REWARDS minigame).
        Schema::create('reward_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->unsignedInteger('points_cost')
                ->comment('Số điểm cần để đổi 1 phần quà');
            $table->integer('stock')->default(0)
                ->comment('Số lượng còn lại trong kho');
            $table->enum('status', ['active', 'locked'])->default('active')
                ->comment('locked = tạm ngừng cho đổi');
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_catalog');
    }
};
