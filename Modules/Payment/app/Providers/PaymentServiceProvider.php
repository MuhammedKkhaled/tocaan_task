<?php

namespace Modules\Payment\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Payment\Contracts\PaymentRepositoryInterface;
use Modules\Payment\Models\Payment;
use Modules\Payment\Policies\PaymentPolicy;
use Modules\Payment\Repositories\PaymentRepository;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(module_path('Payment', 'config/payment.php'), 'payment');
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->register(RouteServiceProvider::class);
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
