<?php

use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Enums\PaymentGateway;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Exceptions\UnsupportedPaymentMethodException;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentGatewayManager;

it('keeps gateway enums and configuration consistent', function (): void {
    $configuredGateways = config('payment.gateways');

    expect(array_keys($configuredGateways))->toBe(PaymentGateway::values());

    foreach (PaymentGateway::cases() as $gateway) {
        $config = $configuredGateways[$gateway->value];

        expect(is_subclass_of($config['driver'], PaymentGatewayInterface::class))->toBeTrue()
            ->and($config['methods'])->not->toBeEmpty();

        foreach ($config['methods'] as $method) {
            expect(PaymentMethod::tryFrom($method))->not->toBeNull();
        }
    }
});

it('resolves every supported gateway and method combination', function (
    string $gateway,
    string $method,
): void {
    $gatewayEnum = PaymentGateway::from($gateway);
    $methodEnum = PaymentMethod::from($method);
    $driver = app(PaymentGatewayManager::class)->resolve($gatewayEnum, $methodEnum);

    expect($driver->gateway())->toBe($gatewayEnum)
        ->and($driver->supports($methodEnum))->toBeTrue();
})->with([
    'Kashier credit card' => ['kashier', 'credit_card'],
    'Paymob credit card' => ['paymob', 'credit_card'],
    'Paymob Apple Pay' => ['paymob', 'apple_pay'],
    'MyFatoorah credit card' => ['myfatoorah', 'credit_card'],
    'MyFatoorah Apple Pay' => ['myfatoorah', 'apple_pay'],
    'MyFatoorah PayPal' => ['myfatoorah', 'paypal'],
]);

it('rejects unsupported gateway and method combinations', function (
    string $gateway,
    string $method,
): void {
    expect(fn () => app(PaymentGatewayManager::class)->resolve(
        PaymentGateway::from($gateway),
        PaymentMethod::from($method),
    ))->toThrow(UnsupportedPaymentMethodException::class);
})->with([
    'Kashier Apple Pay' => ['kashier', 'apple_pay'],
    'Kashier PayPal' => ['kashier', 'paypal'],
    'Paymob PayPal' => ['paymob', 'paypal'],
]);

it('simulates deterministic gateway outcomes', function (
    string $gateway,
    string $method,
): void {
    $gatewayEnum = PaymentGateway::from($gateway);
    $methodEnum = PaymentMethod::from($method);
    $driver = app(PaymentGatewayManager::class)->resolve($gatewayEnum, $methodEnum);
    $payment = new Payment();

    $successful = $driver->charge($payment, $methodEnum);
    $declined = $driver->charge($payment, $methodEnum, ['simulate_outcome' => 'declined']);

    expect($successful->successful)->toBeTrue()
        ->and($successful->transactionId)->toStartWith($gateway.'_')
        ->and($successful->raw)->toMatchArray([
            'gateway' => $gateway,
            'method' => $method,
            'approved' => true,
            'simulated' => true,
        ])
        ->and($declined->successful)->toBeFalse()
        ->and($declined->transactionId)->toBeNull()
        ->and($declined->failureReason)->toContain($gateway)
        ->and($declined->raw['approved'])->toBeFalse();
})->with([
    'Kashier' => ['kashier', 'credit_card'],
    'Paymob' => ['paymob', 'apple_pay'],
    'MyFatoorah' => ['myfatoorah', 'paypal'],
]);
