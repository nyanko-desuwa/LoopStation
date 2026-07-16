<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng trung tâm đơn chuyển giao rác (đơn thường + đơn sự kiện sau này).
        Schema::create('handover_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('facility_id')
                ->constrained('facilities')
                ->restrictOnDelete();
            // NULL cho tới khi manager/staff phân công. Staff phải cùng facility_id.
            $table->foreignId('staff_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            // NULL = đơn thường. FK sang events sẽ thêm khi domain EVENTS được implement.
            $table->unsignedBigInteger('event_id')->nullable()
                ->comment('NULL = đơn thường; có giá trị = đơn tại sự kiện (FK events sau)');
            $table->string('classification_type', 50)->nullable()
                ->comment('cleaned_flattened | cleaned | as_is | mixed');
            $table->decimal('estimated_weight', 10, 2)->nullable()
                ->comment('Khối lượng user ước tính');
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('measurement_units')
                ->nullOnDelete();
            $table->timestamp('appointment_time')->nullable();
            $table->timestamp('expired_at')->nullable()
                ->comment('Mốc auto-cancel nếu user không đến');
            $table->unsignedInteger('reschedule_count')->default(0)
                ->comment('Tối đa 2; vượt → tự hủy');
            $table->enum('status', [
                'pending',
                'approved',
                'completed',
                'rejected',
                'cancelled',
                'expired',
            ])->default('pending');
            $table->string('reject_reason', 500)->nullable()
                ->comment('Bắt buộc khi status = rejected');
            $table->enum('cancel_reason', [
                'user_cancel',
                'staff_cancel',
                'auto_expire',
                'reschedule_exceeded',
            ])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['facility_id', 'appointment_time']);
            $table->index('staff_id');
            $table->index('event_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_requests');
    }
};
