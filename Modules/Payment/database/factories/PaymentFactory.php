<?php

namespace Modules\Payment\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Enums\Currency;
use Modules\Core\Support\ReferenceGenerator;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentGateway;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $gateway = fake()->randomElement(PaymentGateway::cases());
        $method = fake()->randomElement(match ($gateway) {
            PaymentGateway::Kashier => [PaymentMethod::CreditCard],
            PaymentGateway::Paymob => [PaymentMethod::CreditCard, PaymentMethod::ApplePay],
            PaymentGateway::MyFatoorah => PaymentMethod::cases(),
        });

        return [
            'order_id' => Order::factory(),
            'reference' => (new ReferenceGenerator())->paymentReference(),
            'gateway' => $gateway,
            'method' => $method,
            'status' => PaymentStatus::Pending,
            'amount' => fake()->randomFloat(2, 10, 5000),
            'currency' => Currency::USD,
            'gateway_transaction_id' => null,
            'gateway_response' => null,
            'failure_reason' => null,
            'processed_at' => null,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Successful,
            'gateway_transaction_id' => fake()->uuid(),
            'gateway_response' => ['approved' => true],
            'processed_at' => now(),
        ]);
    }
}
