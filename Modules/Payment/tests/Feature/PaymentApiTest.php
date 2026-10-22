<?php

use App\Models\User;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Modules\Order\Models\Order;
use Modules\Payment\Enums\PaymentGateway;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Exceptions\PaymentProcessingInProgressException;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;

function paymentConfirmedOrderFor(User $user): Order
{
    return Order::factory()->for($user)->confirmed()->create([
        'total_amount' => '25.00',
    ]);
}

it('processes and persists separate gateway and method values', function (): void {
    $user = User::factory()->create();
    $order = paymentConfirmedOrderFor($user);

    $this->actingAs($user, 'api')
        ->postJson("/api/orders/{$order->id}/payments", [
            'gateway' => PaymentGateway::Paymob->value,
            'method' => PaymentMethod::ApplePay->value,
        ])
        ->assertCreated()
        ->assertJsonPath('data.gateway', 'paymob')
        ->assertJsonPath('data.method', 'apple_pay')
        ->assertJsonPath('data.status', 'successful')
        ->assertJsonPath('data.amount', '25.00');

    $this->assertDatabaseHas('payments', [
        'order_id' => $order->id,
        'gateway' => 'paymob',
        'method' => 'apple_pay',
        'status' => 'successful',
    ]);
});

it('records a decline and allows a successful retry', function (): void {
    $user = User::factory()->create();
    $order = paymentConfirmedOrderFor($user);
    $this->actingAs($user, 'api');

    $this->postJson("/api/orders/{$order->id}/payments", [
        'gateway' => 'kashier',
        'method' => 'credit_card',
        'payload' => ['simulate_outcome' => 'declined'],
    ])->assertPaymentRequired()
        ->assertJsonPath('data.status', 'failed')
        ->assertJsonPath('data.gateway_response.simulated', true);

    $this->postJson("/api/orders/{$order->id}/payments", [
        'gateway' => 'myfatoorah',
        'method' => 'paypal',
    ])->assertCreated()
        ->assertJsonPath('data.status', 'successful');

    expect($order->payments()->where('status', PaymentStatus::Failed->value)->count())->toBe(1)
        ->and($order->payments()->where('status', PaymentStatus::Successful->value)->count())->toBe(1);
});

it('rejects an unsupported gateway and method combination', function (): void {
    $user = User::factory()->create();
    $order = paymentConfirmedOrderFor($user);

    $this->actingAs($user, 'api')
        ->postJson("/api/orders/{$order->id}/payments", [
            'gateway' => 'kashier',
            'method' => 'apple_pay',
        ])
        ->assertUnprocessable()
        ->assertJsonPath(
            'message',
            "The payment method 'apple_pay' is not supported by gateway 'kashier'.",
        );

    expect($order->payments()->count())->toBe(0);
});

it('prevents another successful payment for the same order', function (): void {
    $user = User::factory()->create();
    $order = paymentConfirmedOrderFor($user);
    $this->actingAs($user, 'api');

    $payload = ['gateway' => 'kashier', 'method' => 'credit_card'];

    $this->postJson("/api/orders/{$order->id}/payments", $payload)->assertCreated();
    $this->postJson("/api/orders/{$order->id}/payments", $payload)
        ->assertConflict()
        ->assertJsonPath('message', 'This order has already been paid.');
});

it('rejects payment for a pending order', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $this->actingAs($user, 'api')
        ->postJson("/api/orders/{$order->id}/payments", [
            'gateway' => 'kashier',
            'method' => 'credit_card',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Order must be confirmed before payment.');
});

it('validates gateway method payload and simulation outcome', function (
    array $payload,
    string $message,
): void {
    $user = User::factory()->create();
    $order = paymentConfirmedOrderFor($user);

    $this->actingAs($user, 'api')
        ->postJson("/api/orders/{$order->id}/payments", $payload)
        ->assertUnprocessable()
        ->assertJsonPath('message', $message);
})->with([
    'gateway' => [
        ['gateway' => 'unknown', 'method' => 'credit_card'],
        "The payment gateway 'unknown' is not supported. Supported gateways: kashier, paymob, myfatoorah.",
    ],
    'method' => [
        ['gateway' => 'kashier', 'method' => 'cash'],
        "The payment method 'cash' is not supported. Supported methods: credit_card, apple_pay, paypal.",
    ],
    'payload' => [
        ['gateway' => 'kashier', 'method' => 'credit_card', 'payload' => 'invalid'],
        'The payment payload must be an object.',
    ],
    'outcome' => [
        [
            'gateway' => 'kashier',
            'method' => 'credit_card',
            'payload' => ['simulate_outcome' => 'timeout'],
        ],
        'The simulation outcome must be either success or declined.',
    ],
]);

it('prevents users from viewing another users payment', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $payment = Payment::factory()->for(paymentConfirmedOrderFor($owner))->create();

    $this->actingAs($otherUser, 'api')
        ->getJson("/api/payments/{$payment->id}")
        ->assertForbidden();
});

it('filters and cursor paginates the current users payments', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = paymentConfirmedOrderFor($user);

    Payment::factory()->for($order)->count(2)->successful()->create();
    Payment::factory()->for(paymentConfirmedOrderFor($otherUser))->successful()->create();

    $firstPage = $this->actingAs($user, 'api')
        ->getJson('/api/payments?status=successful&per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->json();

    expect($firstPage['meta']['next_cursor'])->not->toBeNull();

    $this->getJson('/api/payments?status=successful&per_page=1&cursor='.
        urlencode($firstPage['meta']['next_cursor']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('returns a conflict when the payment lock times out', function (): void {
    $user = User::factory()->create();
    $order = paymentConfirmedOrderFor($user);
    $service = app(PaymentService::class);
    $lock = Mockery::mock(Lock::class);

    $lock->shouldReceive('block')->once()->andThrow(new LockTimeoutException());
    Cache::shouldReceive('lock')
        ->once()
        ->with("order-payment:{$order->id}", 10)
        ->andReturn($lock);

    expect(fn () => $service->process(
        $order,
        PaymentGateway::Kashier,
        PaymentMethod::CreditCard,
    ))->toThrow(PaymentProcessingInProgressException::class)
        ->and((new PaymentProcessingInProgressException())->status())->toBe(409);
});
