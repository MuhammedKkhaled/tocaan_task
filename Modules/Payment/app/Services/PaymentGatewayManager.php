<?php

namespace Modules\Payment\Services;

use Illuminate\Contracts\Container\Container;
use LogicException;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Enums\PaymentGateway;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Exceptions\UnsupportedPaymentMethodException;

final class PaymentGatewayManager
{
    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function resolve(PaymentGateway $gateway, PaymentMethod $method): PaymentGatewayInterface
    {
        $config = config("payment.gateways.{$gateway->value}");

        if (! is_array($config) || ! isset($config['driver'])) {
            throw new LogicException("Missing gateway configuration for {$gateway->value}.");
        }

        $driver = $this->container->make($config['driver'], ['config' => $config]);

        if (! $driver instanceof PaymentGatewayInterface || $driver->gateway() !== $gateway) {
            throw new LogicException("Invalid gateway configuration for {$gateway->value}.");
        }

        if (! $driver->supports($method)) {
            throw new UnsupportedPaymentMethodException($gateway, $method);
        }

        return $driver;
    }
}
