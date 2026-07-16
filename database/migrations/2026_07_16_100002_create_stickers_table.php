<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stickers', function (Blueprint $table): void {
            // Từng sticker trong bộ: weighted drop + bonus lần đầu sở hữu.
            $table->id();
            $table->foreignId('set_id')->constrained('sticker_sets')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('image_url', 500);
            $table->string('rarity', 20)->default('common'); // common | rare | special
            $table->integer('drop_weight')->default(1); // P = weight / sum(weights active in set)
            $table->integer('redeem_quantity_required')->default(1);
            $table->integer('bonus_points')->default(0); // POINT_EARNED sticker_bonus khi first own
            // FK sang educational_contents thêm sau (circular với sticker_set_id trên content).
            $table->unsignedBigInteger('unlocks_content_id')->nullable();
            $table->string('status', 20)->default('active'); // active | locked
            $table->softDeletes();
            $table->timestamps();

            $table->index('set_id', 'idx_stickers_set');
            $table->index('rarity', 'idx_stickers_rarity');
            $table->index('unlocks_content_id', 'idx_stickers_unlocks');
            $table->index('deleted_at', 'idx_stickers_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stickers');
    }
};
