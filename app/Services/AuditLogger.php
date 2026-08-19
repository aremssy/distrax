<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function record(string $action, ?Model $model = null, array $changes = []): void
    {
        AuditLog::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'model' => $model ? $model::class : null,
            'model_id' => $model?->getKey(),
            'changes' => $changes,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 255),
        ]);
    }
}
