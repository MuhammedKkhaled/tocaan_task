<?php

use App\Models\User;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Modules\Payment\Models\Payment;

function stageNineOrderPayload(array $overrides = []): array
{
    return array_replace([
        'currency' => 'USD',
        'notes' => 'Test order',
        'items' => [
            ['product_name' => 'First', 'quantity' => 2, 'price' => '10.01'],
            ['product_name' => 'Second', 'quantity' => 3, 'price' => '0.34'],
        ],
    ], $overrides);
}

it('creates an order with calculated item totals', function (): void {
    $user = User::factory()->create();
    $response = $this->actingAs($user, 'api')->postJson('/api/orders', stageNineOrderPayload());

    $response
        ->assertCreated()
        ->assertJsonPath('data.total_amount', '21.04')
        ->assertJsonPath('data.items.0.unit_price', '10.01')
        ->assertJsonPath('data.items.1.subtotal', '1.02');
    expect($response)->toBeSuccessEnvelope();
});

it('validates order input', function (array $payload, string $errorKey): void {
    $user = User::factory()->create();
    $response = $this->actingAs($user, 'api')->postJson('/api/orders', $payload);

    $response->assertUnprocessable()->assertJsonValidationErrors($errorKey, 'error');
    expect($response)->toBeErrorEnvelope();
})->with([
    'items required' => [stageNineOrderPayload(['items' => []]), 'items'],
    'currency enum' => [stageNineOrderPayload(['currency' => 'GBP']), 'currency'],
    'quantity minimum' => [stageNineOrderPayload(['items' => [[
        'product_name' => 'Invalid',
        'quantity' => 0,
        'price' => '1.00',
    ]]]), 'items.0.quantity'],
    'positive price' => [stageNineOrderPayload(['items' => [[
        'product_name' => 'Invalid',
        'quantity' => 1,
        'price' => '0.00',
    ]]]), 'items.0.price'],
]);

it('filters and cursor paginates orders with stable ordering', function (): void {
    $user = User::factory()->create();
    Order::factory()->for($user)->create();
    $olderConfirmed = Order::factory()->for($user)->confirmed()->create();
    $newerConfirmed = Order::factory()->for($user)->confirmed()->create();
    Order::factory()->confirmed()->create();

    $first = $this->actingAs($user, 'api')
        ->getJson('/api/orders?status=confirmed&per_page=1');
    $first->assertOk()->assertJsonPath('data.0.id', $newerConfirmed->id);
    expect($first)->toBeSuccessEnvelope();

    $second = $this->getJson('/api/orders?status=confirmed&per_page=1&cursor='.
        urlencode($first->json('meta.next_cursor')));
    $second->assertOk()->assertJsonPath('data.0.id', $olderConfirmed->id);
    expect($second)->toBeSuccessEnvelope();

    $invalid = $this->getJson('/api/orders?status=unknown');
    $invalid->assertUnprocessable()->assertJsonPath(
        'message',
        'Status must be one of: pending, confirmed, cancelled.',
    );
    expect($invalid)->toBeErrorEnvelope();
});

it('updates and deletes a pending order', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $updated = $this->actingAs($user, 'api')->putJson("/api/orders/{$order->id}", [
        'notes' => 'Updated notes',
    ]);
    $updated->assertOk()->assertJsonPath('data.notes', 'Updated notes');
    expect($updated)->toBeSuccessEnvelope();

    $deleted = $this->deleteJson("/api/orders/{$order->id}");
    $deleted->assertOk();
    expect($deleted)->toBeSuccessEnvelope();
    $this->assertDatabaseMissing('orders', ['id' => $order->id]);
});

it('blocks deleting an order that has payments', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->confirmed()->create();
    Payment::factory()->for($order)->create();

    $response = $this->actingAs($user, 'api')->deleteJson("/api/orders/{$order->id}");
    $response->assertConflict()->assertJsonPath('message', 'Cannot delete an order that has payments.');
    expect($response)->toBeErrorEnvelope();
});

it('forbids access to another users order', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::factory()->for($owner)->create(['status' => OrderStatus::Pending]);

    $response = $this->actingAs($other, 'api')->getJson("/api/orders/{$order->id}");
    $response->assertForbidden();
    expect($response)->toBeErrorEnvelope();
});
