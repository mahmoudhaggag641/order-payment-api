<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

class PaymentGatewayResolver
{
    public static function make(string $gateway): PaymentGatewayInterface
    {
        $gateways = Config::get('payment.gateways', []);

        if (!isset($gateways[$gateway])) {
            throw new InvalidArgumentException("Payment gateway '{$gateway}' is not configured.");
        }

        $gatewayConfig = $gateways[$gateway];
        $gatewayClass = $gatewayConfig['class'];

        if (!class_exists($gatewayClass)) {
            throw new InvalidArgumentException("Payment gateway class '{$gatewayClass}' does not exist.");
        }

        return new $gatewayClass();
    }

    public static function getAvailableGateways(): array
    {
        return array_keys(Config::get('payment.gateways', []));
    }
}
