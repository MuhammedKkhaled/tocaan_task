<?php

namespace Modules\Payment\Services;

use Illuminate\Contracts\Container\Container;
use LogicException;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Enums\PaymentMethod;
use Modules\Payment\Exceptions\UnsupportedPaymentMethodException;

final class PaymentGatewayManager
{
    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function resolve(PaymentMethod $method): PaymentGatewayInterface
    {
        $config = config("payment.gateways.{$method->value}");

        if (! is_array($config) || ! isset($config['driver'])) {
            throw new UnsupportedPaymentMethodException($method->value);
        }

        $gateway = $this->container->make($config['driver'], ['config' => $config]);

        if (! $gateway instanceof PaymentGatewayInterface || $gateway->method() !== $method) {
            throw new LogicException("Invalid gateway configuration for {$method->value}.");
        }

        return $gateway;
    }
}
