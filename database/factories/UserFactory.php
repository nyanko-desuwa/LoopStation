<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    // hash chung, dùng lại cho mọi user sinh ra từ factory
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->optional()->numerify('0#########'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'avatar_url' => null,
            'must_change_password' => false,
            'role' => 'user',
            'facility_id' => null,
            'is_walk_in' => false,
            'status' => 'active',
        ];
    }

    // chưa xác minh email
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    // nhân viên thuộc 1 cơ sở
    public function staff(?int $facilityId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'staff',
            'facility_id' => $facilityId,
        ]);
    }

    // quản lý cơ sở
    public function manager(?int $facilityId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'manager',
            'facility_id' => $facilityId,
        ]);
    }

    // tài khoản vãng lai từ QR sự kiện, dùng mật khẩu tạm, buộc đổi sau
    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_walk_in' => true,
            'must_change_password' => true,
        ]);
    }

    // tài khoản bị khóa
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'locked',
        ]);
    }
}
