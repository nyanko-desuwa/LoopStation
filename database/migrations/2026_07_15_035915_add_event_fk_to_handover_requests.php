<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // event_id đã tồn tại (nullable) từ migration handover_requests; gắn FK sau khi events tồn tại.
        Schema::table('handover_requests', function (Blueprint $table) {
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('handover_requests', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
        });
    }
};
