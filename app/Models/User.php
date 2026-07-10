<?php

namespace App\Models;

use App\Support\EmailBox;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'phone',
    'email',
    'email_canonical',
    'locale',
    'email_verified_at',
    'password',
    'avatar_url',
    'must_change_password',
    'role',
    'facility_id',
    'is_walk_in',
    'status',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if ($user->email === null) {
                $user->email_canonical = null;

                return;
            }

            $email = trim($user->email);

            if ($email === '') {
                $user->email = null;
                $user->email_canonical = null;

                return;
            }

            $user->email = $email;
            $user->email_canonical = EmailBox::normalize($email);
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'must_change_password' => 'boolean',
            'is_walk_in' => 'boolean',
            'facility_id' => 'integer',
            'password' => 'hashed',
        ];
    }
}
