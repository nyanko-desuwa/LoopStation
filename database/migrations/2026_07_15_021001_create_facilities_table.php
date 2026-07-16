<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->enum('type', ['station', 'office'])
                ->comment('station = trạm thu hồi, office = văn phòng công ty');
            $table->string('address', 300)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->enum('status', ['active', 'locked'])->default('active')
                ->comment('locked ẩn cơ sở khỏi portal user');
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
            $table->index('deleted_at');
        });

        // users.facility_id đã có từ migration users; gắn FK sau khi facilities tồn tại.
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('facility_id')
                ->references('id')
                ->on('facilities')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['facility_id']);
        });

        Schema::dropIfExists('facilities');
    }
};
