<?php

use App\Models\User;
use App\Support\EmailBox;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            User::query()
                ->withTrashed()
                ->orderBy('id')
                ->chunkById(200, function ($users): void {
                    foreach ($users as $user) {
                        if ($user->email === null) {
                            $user->forceFill(['email_canonical' => null])->saveQuietly();

                            continue;
                        }

                        $user->forceFill([
                            'email_canonical' => EmailBox::normalize($user->email),
                        ])->saveQuietly();
                    }
                });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function (): void {
            User::query()
                ->withTrashed()
                ->orderBy('id')
                ->chunkById(200, function ($users): void {
                    foreach ($users as $user) {
                        $user->forceFill(['email_canonical' => null])->saveQuietly();
                    }
                });
        });
    }
};
