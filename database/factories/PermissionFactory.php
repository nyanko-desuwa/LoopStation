<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $resource = fake()->unique()->slug(1);
        $action = fake()->randomElement(['view', 'create', 'update', 'delete']);

        return [
            'code' => "{$resource}.{$action}",
            'resource' => $resource,
            'action' => $action,
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            // Quyền custom do manager thêm; state system() dùng cho seed.
            'is_system' => false,
        ];
    }

    // Quyền gốc do hệ thống seed - không cho xóa qua API.
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }
}
