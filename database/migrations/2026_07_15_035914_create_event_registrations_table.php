<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Đăng ký tham dự sự kiện; unique (event_id, user_id). walkin tạo cùng tài khoản QR.
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->enum('registration_type', ['visit', 'handover', 'walkin'])
                ->comment('visit = tham quan | handover = đăng ký nộp đồ | walkin = khách vãng lai');
            $table->enum('status', ['registered', 'attended', 'absent'])
                ->default('registered');
            $table->enum('minigame_status', ['not_eligible', 'unlocked', 'played'])
                ->default('not_eligible');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['event_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
