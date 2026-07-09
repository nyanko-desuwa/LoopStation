<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Mật khẩu dùng chung cho các user sinh ra từ factory, hash 1 lần rồi tái dùng.
     */
    protected static ?string $password;

    /**
     * User mặc định: khách hàng thường (role = user), không gắn cơ sở.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Chưa xác minh email.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Nhân viên thuộc một cơ sở.
     */
    public function staff(?int $facilityId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'staff',
            'facility_id' => $facilityId,
        ]);
    }

    /**
     * Quản lý cơ sở.
     */
    public function manager(?int $facilityId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'manager',
            'facility_id' => $facilityId,
        ]);
    }

    /**
     * Tài khoản vãng lai tạo tự động khi quét QR sự kiện: dùng mật khẩu tạm,
     * buộc đổi ở lần đăng nhập sau.
     */
    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_walk_in' => true,
            'must_change_password' => true,
        ]);
    }

    /**
     * Tài khoản bị khóa.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'locked',
        ]);
    }
}
