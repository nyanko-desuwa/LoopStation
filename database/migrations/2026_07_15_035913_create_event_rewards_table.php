<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quà vật lý minigame của event; remaining trừ dần. Khác REWARD_CATALOG (đổi bằng điểm ví).
        Schema::create('event_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedInteger('quantity')->default(0)
                ->comment('Số lượng ban đầu');
            $table->unsignedInteger('remaining')->default(0)
                ->comment('Còn lại; trừ dần khi minigame trúng thưởng');
            $table->timestamp('created_at')->useCurrent();

            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_rewards');
    }
};
