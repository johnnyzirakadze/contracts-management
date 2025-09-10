<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class AuditLogger
{
    public static function log(?Authenticatable $user, string $table, ?int $rowId, string $action, array $oldValues = null, array $newValues = null, ?Request $request = null): void
    {
        $request = $request ?? request();
        AuditLog::create([
            'user_id' => $user?->getAuthIdentifier(),
            'table_name' => $table,
            'row_id' => $rowId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}


