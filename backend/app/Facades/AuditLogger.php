<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

final class AuditLogger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'audit_logger.service';
    }
}
