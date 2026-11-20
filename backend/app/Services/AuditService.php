<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public static function log(
        string $action,
        Model $subject,
        ?User $user = null,
        array $properties = [],
        ?string $ip = null,
    ): void {
        ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => class_basename($subject),
            'subject_id' => $subject->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => $ip ?? request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
