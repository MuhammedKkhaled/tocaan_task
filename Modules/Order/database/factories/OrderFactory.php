<?php

namespace Modules\Order\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Enums\Currency;
use Modules\Core\Support\ReferenceGenerator;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'number' => (new ReferenceGenerator())->orderNumber(),
            'status' => OrderStatus::Pending,
            'total_amount' => fake()->randomFloat(2, 10, 5000),
            'currency' => Currency::USD,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Confirmed]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Cancelled]);
    }
}
