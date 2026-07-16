<?php

namespace Database\Factories;

use App\Models\Redemption;
use App\Models\RewardCatalog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redemption>
 */
class RedemptionFactory extends Factory
{
    protected $model = Redemption::class;

    public function definition(): array
    {
        $quantity = 1;
        $cost = fake()->numberBetween(10, 50);

        return [
            'user_id' => User::factory(),
            'reward_id' => RewardCatalog::factory(),
            'points_spent' => $cost * $quantity,
            'quantity' => $quantity,
            'status' => Redemption::STATUS_PENDING,
            'fulfillment_method' => Redemption::METHOD_PICKUP,
            'recipient_name' => null,
            'recipient_phone' => null,
            'shipping_address' => null,
            'shipping_note' => null,
            'transaction_id' => null,
            'fulfilled_by_id' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function delivery(): static
    {
        return $this->state(fn () => [
            'fulfillment_method' => Redemption::METHOD_DELIVERY,
            'recipient_name' => fake()->name(),
            'recipient_phone' => '09'.fake()->numerify('########'),
            'shipping_address' => fake()->address(),
        ]);
    }

    public function fulfilled(): static
    {
        return $this->state(fn () => ['status' => Redemption::STATUS_FULFILLED]);
    }
}
