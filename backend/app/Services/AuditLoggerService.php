<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

// we should prolly use traits and use it in models where logging are necessary
// or better use laravel-audits for more powerful and feature-rich logging
final class AuditLoggerService
{
    public function log(int $userId, string $action, ?Model $model = null, array $metadata = []): AuditLog
    {
        return AuditLog::query()
            ->create([
                'user_id' => $userId,
                'action' => $action,
                'model_type' => $model?->getMorphClass(),
                'model_id' => $model?->getKey(),
                'metadata' => $metadata,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
    }
}
