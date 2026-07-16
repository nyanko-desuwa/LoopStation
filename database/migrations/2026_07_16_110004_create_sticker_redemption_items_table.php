<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sticker_redemption_items', function (Blueprint $table): void {
            // Snapshot vật phẩm đã giao mỗi lần đổi; item_name/image chốt lịch sử, không đổi theo catalog.
            $table->id();
            $table->foreignId('redemption_id')->constrained('sticker_redemptions')->cascadeOnDelete();
            $table->foreignId('reward_item_id')->nullable()->constrained('sticker_reward_items')->nullOnDelete();
            $table->string('item_name', 150); // snapshot tên tại thời điểm đổi.
            $table->string('item_image_url', 500)->nullable();
            $table->integer('quantity');
            $table->timestamp('created_at')->useCurrent();

            $table->index('redemption_id', 'idx_redeem_items_redemption');
            $table->index('reward_item_id', 'idx_redeem_items_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sticker_redemption_items');
    }
};
