<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sticker_reward_rules', function (Blueprint $table): void {
            // Bó quà: 1 lần đổi sticker X ra những vật phẩm nào, mỗi thứ bao nhiêu.
            $table->id();
            $table->foreignId('sticker_id')->constrained('stickers')->cascadeOnDelete();
            $table->foreignId('reward_item_id')->constrained('sticker_reward_items')->restrictOnDelete();
            $table->integer('quantity')->default(1); // đổi 1 lần ra bao nhiêu vật phẩm.
            $table->string('status', 20)->default('active'); // active | locked
            $table->timestamps();

            $table->unique(['sticker_id', 'reward_item_id'], 'uq_reward_rule');
            $table->index('sticker_id', 'idx_reward_rules_sticker');
            $table->index('reward_item_id', 'idx_reward_rules_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sticker_reward_rules');
    }
};
