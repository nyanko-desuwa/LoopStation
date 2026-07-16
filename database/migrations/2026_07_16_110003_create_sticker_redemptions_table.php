<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sticker_redemptions', function (Blueprint $table): void {
            // Đổi sticker vật lý: trừ user_stickers.quantity; pickup tại cơ sở hoặc delivery ship.
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sticker_id')->constrained('stickers')->restrictOnDelete();
            $table->integer('quantity_used'); // số sticker ảo đã trừ, chốt theo redeem_quantity_required lúc đổi.
            $table->string('fulfillment_method', 20)->default('pickup'); // pickup | delivery
            $table->string('status', 20)->default('pending'); // pending | shipping | fulfilled | cancelled
            $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name', 150)->nullable();
            $table->string('recipient_phone', 20)->nullable();
            $table->string('shipping_address', 500)->nullable();
            $table->string('shipping_note', 300)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'idx_sticker_redeem_user');
            $table->index(['user_id', 'status'], 'idx_sticker_redeem_user_status');
            $table->index('sticker_id', 'idx_sticker_redeem_sticker');
            $table->index('facility_id', 'idx_sticker_redeem_facility');
            $table->index('fulfillment_method', 'idx_sticker_redeem_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sticker_redemptions');
    }
};
