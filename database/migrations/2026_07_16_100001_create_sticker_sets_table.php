<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sticker_sets', function (Blueprint $table): void {
            // Bộ sưu tập sticker theo chủ đề; locked = tạm không rơi sticker.
            $table->id();
            $table->string('name', 150);
            $table->string('theme', 100)->nullable();
            $table->string('cover_image_url', 500)->nullable();
            $table->string('status', 20)->default('active'); // active | locked
            $table->softDeletes();
            $table->timestamps();

            $table->index('status', 'idx_sticker_sets_status');
            $table->index('deleted_at', 'idx_sticker_sets_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sticker_sets');
    }
};
