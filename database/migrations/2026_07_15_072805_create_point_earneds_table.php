<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only lịch sử cộng điểm. points luôn dương. Table name khớp schema.sql.
        Schema::create('point_earned', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')
                ->constrained('user_wallets')
                ->cascadeOnDelete();
            $table->unsignedInteger('points')
                ->comment('Luôn dương - số điểm kiếm được');
            $table->enum('source_type', [
                'handover',
                'event_minigame',
                'content_read',
                'manager_adjust',
                'redemption_refund',
                'sticker_bonus',
            ]);
            $table->unsignedBigInteger('reference_id')->nullable()
                ->comment('ID nguồn theo source_type; NULL với manager_adjust');
            $table->string('description', 300)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['wallet_id', 'created_at']);
            $table->index(['source_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_earned');
    }
};
