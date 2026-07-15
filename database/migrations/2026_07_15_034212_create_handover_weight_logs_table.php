<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only: nhiều lần cân thực tế cho 1 đơn; audit ai cân, lúc nào.
        Schema::create('handover_weight_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')
                ->constrained('handover_requests')
                ->cascadeOnDelete();
            $table->decimal('weight', 10, 2)
                ->comment('Giá trị cân thực tế');
            $table->foreignId('unit_id')
                ->constrained('measurement_units')
                ->restrictOnDelete();
            $table->foreignId('recorded_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('request_id');
            $table->index('recorded_by');
            $table->index('unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_weight_logs');
    }
};
