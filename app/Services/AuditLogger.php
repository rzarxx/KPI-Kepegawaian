<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(
        string $action,
        ?User $actor = null,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $context = null,
    ): AuditLog {
        /** @var Request|null $request */
        $request = app()->bound('request') ? request() : null;

        return AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'context' => $context,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
