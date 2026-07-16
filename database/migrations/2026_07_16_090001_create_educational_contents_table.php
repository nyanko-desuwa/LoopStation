<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('educational_contents', function (Blueprint $table): void {
            // Bài học giáo dục môi trường: staff soạn (pending) → manager duyệt (published/rejected).
            $table->id();
            $table->string('title', 200);
            $table->text('content'); // HTML rich text; ảnh nhúng qua <img src="...">.
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('status', 20)->default('pending'); // pending | published | rejected
            $table->integer('timer_seconds')->default(120); // đọc tối thiểu để nhận điểm.
            $table->integer('points_reward')->default(0);
            // sticker_set_id: FK sang STICKER_SETS thêm khi domain sticker làm; giờ để cột trơn.
            $table->unsignedBigInteger('sticker_set_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status', 'idx_content_status');
            $table->index('author_id', 'idx_content_author');
            $table->index('sticker_set_id', 'idx_content_sticker_set');
            $table->index('deleted_at', 'idx_content_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('educational_contents');
    }
};
