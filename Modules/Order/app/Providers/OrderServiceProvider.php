<?php

namespace Modules\Order\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Order\Models\Order;
use Modules\Order\Policies\OrderPolicy;

class OrderServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
    }

    public function boot(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
