<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\AuditLoggerService;
use Illuminate\Support\ServiceProvider;

final class AuditLoggerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('audit_logger.service', fn () => new AuditLoggerService());
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
        return [AuditLoggerService::class];
    }
}
