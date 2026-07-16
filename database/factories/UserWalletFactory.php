<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserWallet>
 */
class UserWalletFactory extends Factory
{
    protected $model = UserWallet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => 0,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function withBalance(int $balance): static
    {
        return $this->state(fn () => ['balance' => $balance]);
    }
}
