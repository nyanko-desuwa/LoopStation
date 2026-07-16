<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mapping role (user/staff/manager) → permission. Không có bảng roles riêng.
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->enum('role', ['user', 'staff', 'manager'])
                ->comment('Matches users.role domain');
            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete();
            // Manager cấu hình mapping; seed hệ thống để null.
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['role', 'permission_id']);
            $table->index('permission_id');
            $table->index('role');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
