<?php

namespace Modules\Payment\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Payment\Models\Payment;
use Modules\Payment\Policies\PaymentPolicy;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
    }

    public function boot(): void
    {
        Gate::policy(Payment::class, PaymentPolicy::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
