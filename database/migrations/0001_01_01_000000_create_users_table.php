<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 20)->unique()->nullable();
            $table->string('email', 150)->nullable();
            $table->string('email_canonical', 150)->nullable()->unique();
            $table->string('locale', 10)->default('vi');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable()
                ->comment('Hash mật khẩu. NULL với tài khoản walk-in');
            $table->rememberToken();
            $table->string('avatar_url', 500)->nullable();
            $table->boolean('must_change_password')->default(false)
                ->comment('true = Tạm dùng mật khẩu tạm, buộc đổi lần đăng nhập kế');
            $table->enum('role', ['user', 'staff', 'manager'])->default('user');
            $table->unsignedBigInteger('facility_id')->nullable()
                ->comment('Cơ sở trực thuộc. Bắt buộc với staff/manager. NULL với user');
            $table->boolean('is_walk_in')->default(false)
                ->comment('true = tài khoản tạo tự động từ QR sự kiện');
            $table->enum('status', ['active', 'locked'])->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->index('role');
            $table->index('facility_id');
            $table->index('deleted_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
