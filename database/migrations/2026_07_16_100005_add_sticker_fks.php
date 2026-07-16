<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // educational_contents.sticker_set_id đã có cột; gắn FK sau khi sticker_sets tồn tại.
        Schema::table('educational_contents', function (Blueprint $table): void {
            $table->foreign('sticker_set_id', 'fk_content_sticker_set')
                ->references('id')
                ->on('sticker_sets')
                ->nullOnDelete();
        });

        // stickers.unlocks_content_id → educational_contents (circular FK, gắn sau cả hai bảng).
        Schema::table('stickers', function (Blueprint $table): void {
            $table->foreign('unlocks_content_id', 'fk_stickers_unlocks')
                ->references('id')
                ->on('educational_contents')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stickers', function (Blueprint $table): void {
            $table->dropForeign('fk_stickers_unlocks');
        });

        Schema::table('educational_contents', function (Blueprint $table): void {
            $table->dropForeign('fk_content_sticker_set');
        });
    }
};
