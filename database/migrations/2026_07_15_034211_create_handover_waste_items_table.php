<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // N-N: 1 đơn có nhiều loại rác; unique (request_id, waste_type_id).
        Schema::create('handover_waste_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')
                ->constrained('handover_requests')
                ->cascadeOnDelete();
            $table->foreignId('waste_type_id')
                ->constrained('waste_types')
                ->restrictOnDelete();
            $table->decimal('weight', 10, 2)
                ->comment('Khối lượng user khai cho từng loại');
            $table->foreignId('unit_id')
                ->constrained('measurement_units')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['request_id', 'waste_type_id']);
            $table->index('waste_type_id');
            $table->index('unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_waste_items');
    }
};
