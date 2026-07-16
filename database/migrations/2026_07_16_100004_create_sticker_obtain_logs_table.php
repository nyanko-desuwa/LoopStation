<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sticker_obtain_logs', function (Blueprint $table): void {
            // Append-only: mỗi lần nhận sticker (kể cả trùng).
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sticker_id')->constrained('stickers')->cascadeOnDelete();
            $table->foreignId('source_content_id')
                ->nullable()
                ->constrained('educational_contents')
                ->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at'], 'idx_obtain_logs_user');
            $table->index('sticker_id', 'idx_obtain_logs_sticker');
            $table->index('source_content_id', 'idx_obtain_logs_content');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sticker_obtain_logs');
    }
};
