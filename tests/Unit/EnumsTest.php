<?php

use Modules\Core\Enums\Currency;
use Modules\Order\Enums\OrderStatus;
use Modules\Payment\Enums\PaymentGateway;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Enums\PaymentStatus;

it('allows only valid order status transitions', function (
    OrderStatus $from,
    OrderStatus $to,
    bool $allowed,
): void {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    'pending to pending' => [OrderStatus::Pending, OrderStatus::Pending, false],
    'pending to confirmed' => [OrderStatus::Pending, OrderStatus::Confirmed, true],
    'pending to cancelled' => [OrderStatus::Pending, OrderStatus::Cancelled, true],
    'confirmed to pending' => [OrderStatus::Confirmed, OrderStatus::Pending, false],
    'confirmed to confirmed' => [OrderStatus::Confirmed, OrderStatus::Confirmed, false],
    'confirmed to cancelled' => [OrderStatus::Confirmed, OrderStatus::Cancelled, true],
    'cancelled to pending' => [OrderStatus::Cancelled, OrderStatus::Pending, false],
    'cancelled to confirmed' => [OrderStatus::Cancelled, OrderStatus::Confirmed, false],
    'cancelled to cancelled' => [OrderStatus::Cancelled, OrderStatus::Cancelled, false],
]);

it('returns every backed enum value', function (string $enum, array $values): void {
    expect($enum::values())->toBe($values);
})->with([
    'currencies' => [Currency::class, ['USD', 'EUR', 'EGP']],
    'order statuses' => [OrderStatus::class, ['pending', 'confirmed', 'cancelled']],
    'payment gateways' => [PaymentGateway::class, ['kashier', 'paymob', 'myfatoorah']],
    'payment methods' => [PaymentMethod::class, ['credit_card', 'apple_pay', 'paypal']],
    'payment statuses' => [PaymentStatus::class, ['pending', 'successful', 'failed']],
]);
