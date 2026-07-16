<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_stickers', function (Blueprint $table): void {
            // Inventory: 1 dòng / (user, sticker). quantity giảm khi redeem; total_obtained không giảm.
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sticker_id')->constrained('stickers')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('total_obtained')->default(0);
            $table->timestamp('first_obtained_at')->nullable();
            $table->timestamp('last_obtained_at')->nullable();

            $table->unique(['user_id', 'sticker_id'], 'uk_user_sticker');
            $table->index('sticker_id', 'idx_user_stickers_sticker');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stickers');
    }
};
