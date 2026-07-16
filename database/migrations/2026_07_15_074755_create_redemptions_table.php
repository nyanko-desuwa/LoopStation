<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lịch sử đổi quà bằng điểm. Snapshot points_spent; link POINT_SPENT.
        Schema::create('redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('reward_id')
                ->constrained('reward_catalog')
                ->restrictOnDelete();
            $table->unsignedInteger('points_spent')
                ->comment('Điểm đã trừ tại thời điểm đổi (snapshot)');
            $table->unsignedInteger('quantity')->default(1);
            $table->enum('status', ['pending', 'shipping', 'fulfilled', 'cancelled'])
                ->default('pending');
            $table->enum('fulfillment_method', ['pickup', 'delivery'])
                ->default('pickup');
            $table->string('recipient_name', 150)->nullable();
            $table->string('recipient_phone', 20)->nullable();
            $table->string('shipping_address', 500)->nullable();
            $table->string('shipping_note', 300)->nullable();
            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('point_spent')
                ->nullOnDelete();
            $table->foreignId('fulfilled_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('reward_id');
            $table->index('transaction_id');
            $table->index('fulfillment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redemptions');
    }
};
