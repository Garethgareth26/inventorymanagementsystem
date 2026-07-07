<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Cross-cutting audit trail writer (SAD §7.5, ADR-004).
 *
 * This is the single, authorised way to write to `audit_logs`.
 * It must be called from every Service method that performs a
 * create, update, or delete — never directly from Livewire components.
 *
 * Usage:
 *   AuditLogger::log($actor, 'supplier.create', $supplier, null, $supplier->toArray());
 *   AuditLogger::log($actor, 'parameter.apply', $param, $old, $new);
 */
final class AuditLogger
{
    /**
     * Write a single audit log row.
     *
     * @param  User  $actor  The authenticated user who performed the action.
     * @param  string  $action  Verb describing the action (e.g. 'supplier.create',
     *                          'stock.mutate', 'parameter.apply', 'po.status_change').
     * @param  Model  $subject  The Eloquent model instance that was changed.
     * @param  mixed  $old  Snapshot of changed attributes BEFORE the action.
     *                      Pass null for create operations.
     * @param  mixed  $new  Snapshot of changed attributes AFTER the action.
     *                      Pass null for delete operations.
     * @param  Request|null  $request  HTTP request, used to capture ip/user-agent context.
     *                                 If null, context is omitted (e.g. during queue jobs).
     */
    public static function log(
        User $actor,
        string $action,
        Model $subject,
        mixed $old,
        mixed $new,
        ?Request $request = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->getKey(),
            'old_values' => self::normalise($old),
            'new_values' => self::normalise($new),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * Normalise old/new values to array|null.
     *
     * Accepts: array, Eloquent model, null, or any scalar.
     *
     * @return array<string, mixed>|null
     */
    private static function normalise(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Model) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return $value;
        }

        // Scalar or other — wrap in an array
        return ['value' => $value];
    }
}
