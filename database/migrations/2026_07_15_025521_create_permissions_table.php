<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Danh mục quyền RBAC: code dạng resource.action (VD facility.create).
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique()
                ->comment('resource.action, e.g. handover.create');
            $table->string('resource', 50)
                ->comment('handover, event, content, ...');
            $table->string('action', 50)
                ->comment('create, approve, publish, ...');
            $table->string('name', 150)
                ->comment('Human-readable label for manager UI');
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(true)
                ->comment('true = seeded by system, false = manager-added');
            $table->timestamps();

            $table->unique(['resource', 'action']);
            $table->index('resource');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
