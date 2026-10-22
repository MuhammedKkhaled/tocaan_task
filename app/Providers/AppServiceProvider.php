<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class); // app/Providers/TelescopeServiceProvider
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->preventNPlusOne();
        $this->logSlowQueries();
        $this->configureRateLimiting();

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            foreach ($openApi->paths as $path) {
                if ($path->path === 'auth/refresh' && isset($path->operations['post'])) {
                    $path->operations['post']->security = null;
                }
            }
        });
    }

    private function preventNPlusOne(): void
    {
        Model::preventLazyLoading();

        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
            $message = sprintf('Lazy loading [%s] on [%s]', $relation, $model::class);

            if (app()->isProduction()) {
                logger()->warning($message);

                return;
            }

            throw new \LogicException($message);
        });
    }

    private function logSlowQueries(): void
    {
        DB::whenQueryingForLongerThan(500, function (Connection $connection, QueryExecuted $query): void {
            logger()->debug('Slow Query Detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ]);
        });

        DB::listen(function (QueryExecuted $query): void {
            if ($query->time > 200) {
                logger()->warning('Single slow query', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
            }
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->input('email').'|'.$request->ip());
        });
    }
}
