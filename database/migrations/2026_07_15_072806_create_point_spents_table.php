<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only lịch sử trừ điểm. points luôn dương.
        Schema::create('point_spent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')
                ->constrained('user_wallets')
                ->cascadeOnDelete();
            $table->unsignedInteger('points')
                ->comment('Luôn dương - số điểm đã tiêu/bị trừ');
            $table->enum('source_type', ['redemption', 'manager_adjust']);
            $table->unsignedBigInteger('reference_id')->nullable()
                ->comment('ID redemption; NULL với manager_adjust');
            $table->string('description', 300)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['wallet_id', 'created_at']);
            $table->index(['source_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_spent');
    }
};
