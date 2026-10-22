<?php

use App\Models\User;
use Modules\Core\Enums\Currency;
use Modules\Core\Support\ReferenceGenerator;
use Modules\Order\Contracts\OrderRepositoryInterface;
use Modules\Order\Exceptions\OrderTotalExceededException;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderService;

it('rounds item prices before calculating the order total', function (): void {
    $user = new User();
    $user->id = 1;
    $repository = Mockery::mock(OrderRepositoryInterface::class);
    $storedItems = [];

    $repository->shouldReceive('createForUser')
        ->once()
        ->with($user, Mockery::on(function (array $attributes): bool {
            expect($attributes['total_amount'])->toBe('21.04')
                ->and($attributes['currency'])->toBe(Currency::USD);

            return true;
        }))
        ->andReturnUsing(function (User $owner, array $attributes): Order {
            $order = new Order($attributes);
            $order->id = 10;
            $order->user_id = $owner->getKey();

            return $order;
        });
    $repository->shouldReceive('replaceItems')
        ->once()
        ->andReturnUsing(function (Order $order, array $items) use (&$storedItems): void {
            $storedItems = $items;
        });
    $repository->shouldReceive('withItems')->once()->andReturnArg(0);

    $order = (new OrderService($repository, new ReferenceGenerator()))->create($user, [
        'items' => [
            ['product_name' => 'First', 'quantity' => 2, 'price' => '10.005'],
            ['product_name' => 'Second', 'quantity' => 3, 'price' => '0.335'],
        ],
    ]);

    expect($order->total_amount)->toBe('21.04')
        ->and($storedItems)->toBe([
            ['product_name' => 'First', 'quantity' => 2, 'unit_price' => '10.01', 'subtotal' => '20.02'],
            ['product_name' => 'Second', 'quantity' => 3, 'unit_price' => '0.34', 'subtotal' => '1.02'],
        ]);
});

it('rejects an order total beyond the database limit', function (): void {
    $repository = Mockery::mock(OrderRepositoryInterface::class);
    $repository->shouldNotReceive('createForUser');

    $user = new User();
    $user->id = 1;
    $service = new OrderService($repository, new ReferenceGenerator());

    expect(fn () => $service->create($user, [
        'items' => [[
            'product_name' => 'Too expensive',
            'quantity' => 10_000,
            'price' => '99999999.99',
        ]],
    ]))->toThrow(OrderTotalExceededException::class);
});
