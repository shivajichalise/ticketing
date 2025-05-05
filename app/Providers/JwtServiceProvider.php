<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\JwtService;
use Illuminate\Support\ServiceProvider;

final class JwtServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('jwt.service', fn () => new JwtService());
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [JwtService::class];
    }
}
