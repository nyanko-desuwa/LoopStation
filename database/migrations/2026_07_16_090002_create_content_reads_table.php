<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_reads', function (Blueprint $table): void {
            // Lượt đọc bài: enforce timer tối thiểu + quota rewarded theo ngày.
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('content_id')->constrained('educational_contents')->cascadeOnDelete();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable(); // NULL = chưa đủ timer.
            $table->boolean('rewarded')->default(false); // true = đã cộng điểm.
            $table->date('read_date'); // local date, reset quota mỗi ngày.

            // Append-only theo nghiệp vụ; chỉ update completed_at/rewarded 1 lần khi hoàn thành.
            $table->index(['user_id', 'read_date'], 'idx_reads_user_date');
            $table->index('content_id', 'idx_reads_content');
            $table->index(['content_id', 'read_date'], 'idx_reads_content_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reads');
    }
};
