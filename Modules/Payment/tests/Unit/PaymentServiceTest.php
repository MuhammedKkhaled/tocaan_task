<?php

use Modules\Core\Enums\Currency;
use Modules\Core\Support\ReferenceGenerator;
use Modules\Order\Contracts\OrderRepositoryInterface;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Models\Order;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Contracts\PaymentRepositoryInterface;
use Modules\Payment\DTOs\GatewayResult;
use Modules\Payment\Enums\PaymentGateway;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentGatewayManager;
use Modules\Payment\Services\PaymentService;

final class StageNineFakeGateway implements PaymentGatewayInterface
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config = [])
    {
    }

    public function gateway(): PaymentGateway
    {
        return PaymentGateway::Kashier;
    }

    public function supports(PaymentMethod $method): bool
    {
        return in_array($method->value, $this->config['methods'] ?? [], true);
    }

    public function charge(Payment $payment, PaymentMethod $method, array $payload = []): GatewayResult
    {
        if (($payload['simulate_outcome'] ?? 'success') === 'declined') {
            return new GatewayResult(false, failureReason: 'Fake decline.', raw: ['approved' => false]);
        }

        return new GatewayResult(true, 'fake-transaction', raw: ['approved' => true]);
    }
}

it('persists fake gateway success and failure results', function (
    string $outcome,
    PaymentStatus $expectedStatus,
): void {
    config()->set('payment.gateways.kashier', [
        'driver' => StageNineFakeGateway::class,
        'methods' => [PaymentMethod::CreditCard->value],
    ]);

    $order = new Order([
        'status' => OrderStatus::Confirmed,
        'total_amount' => '19.99',
        'currency' => Currency::USD,
    ]);
    $order->id = 5;

    $orders = Mockery::mock(OrderRepositoryInterface::class);
    $orders->shouldReceive('lock')->once()->with($order)->andReturn($order);

    $payments = Mockery::mock(PaymentRepositoryInterface::class);
    $payments->shouldReceive('successfulPaymentExists')->once()->with($order)->andReturnFalse();
    $payments->shouldReceive('createForOrder')
        ->once()
        ->with($order, Mockery::on(fn (array $attributes): bool =>
            $attributes['gateway'] === PaymentGateway::Kashier
            && $attributes['method'] === PaymentMethod::CreditCard
            && $attributes['status'] === PaymentStatus::Pending
            && $attributes['amount'] === '19.99'))
        ->andReturnUsing(function (Order $owner, array $attributes): Payment {
            $payment = new Payment($attributes);
            $payment->id = 8;
            $payment->order_id = $owner->getKey();

            return $payment;
        });
    $payments->shouldReceive('updatePayment')
        ->once()
        ->andReturnUsing(function (Payment $payment, array $attributes): Payment {
            $payment->forceFill($attributes);

            return $payment;
        });

    $service = new PaymentService(
        $payments,
        $orders,
        app(PaymentGatewayManager::class),
        new ReferenceGenerator(),
    );

    $payment = $service->process(
        $order,
        PaymentGateway::Kashier,
        PaymentMethod::CreditCard,
        ['simulate_outcome' => $outcome],
    );

    expect($payment->status)->toBe($expectedStatus)
        ->and($payment->gateway_transaction_id)->toBe(
            $expectedStatus === PaymentStatus::Successful ? 'fake-transaction' : null,
        )
        ->and($payment->failure_reason)->toBe(
            $expectedStatus === PaymentStatus::Failed ? 'Fake decline.' : null,
        );
})->with([
    'successful' => ['success', PaymentStatus::Successful],
    'failed' => ['declined', PaymentStatus::Failed],
]);
