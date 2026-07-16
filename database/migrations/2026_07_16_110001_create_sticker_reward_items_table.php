<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sticker_reward_items', function (Blueprint $table): void {
            // Danh mục vật phẩm vật lý làm quà đổi sticker; manager CRUD ảnh + tên + tồn kho.
            $table->id();
            $table->string('name', 150);
            $table->string('image_url', 500)->nullable();
            $table->text('description')->nullable();
            $table->integer('stock')->default(0); // trừ dần khi đổi, manager tự chỉnh.
            $table->string('status', 20)->default('active'); // active | locked
            $table->softDeletes();
            $table->timestamps();

            $table->index('status', 'idx_reward_items_status');
            $table->index('deleted_at', 'idx_reward_items_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sticker_reward_items');
    }
};
