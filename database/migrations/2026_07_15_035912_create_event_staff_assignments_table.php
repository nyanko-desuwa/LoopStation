<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // N-N event ↔ staff. Overlap-time chặn ở tầng service (schema.sql dùng trigger).
        Schema::create('event_staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();
            $table->foreignId('staff_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();

            $table->unique(['event_id', 'staff_id']);
            $table->index('staff_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_staff_assignments');
    }
};
