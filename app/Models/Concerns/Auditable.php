<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Drop `use Auditable;` onto any model to automatically write an
 * audit_logs entry whenever a record is created, updated, soft-deleted,
 * restored, or permanently deleted.
 *
 * Only real, user-facing writes get logged. Internal bookkeeping that
 * uses saveQuietly() / updateQuietly() / saved-via-forceFill-then-
 * saveQuietly() — recalculateTotals(), stock syncing, net-income
 * recompute, etc. across the Fuel* models — deliberately fires no
 * Eloquent events at all, so none of that noise ends up here. That
 * keeps the trail focused on actions a person actually took rather
 * than every derived-total recompute that follows from them.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            AuditLog::record('created', $model, [], static::auditableAttributes($model));
        });

        static::updated(function (Model $model): void {
            $changes = static::auditableAttributes($model, $model->getChanges());

            if (empty($changes)) {
                return;
            }

            $original = collect($changes)
                ->keys()
                ->mapWithKeys(fn ($key) => [$key => $model->getOriginal($key)])
                ->toArray();

            AuditLog::record('updated', $model, $original, $changes);
        });

        static::deleted(function (Model $model): void {
            $forceDeleted = method_exists($model, 'isForceDeleting') && $model->isForceDeleting();

            AuditLog::record(
                $forceDeleted ? 'force_deleted' : 'deleted',
                $model,
                static::auditableAttributes($model)
            );
        });

        // Only soft-deletable models expose a restored() event hook —
        // guard the registration so this trait works on plain models too.
        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model): void {
                AuditLog::record('restored', $model);
            });
        }
    }

    /**
     * Attributes worth recording. Strips timestamp/soft-delete columns
     * so old/new diffs on the audit log page stay readable.
     */
    protected static function auditableAttributes(Model $model, ?array $attributes = null): array
    {
        $attributes ??= $model->getAttributes();

        return collect($attributes)
            ->except(['created_at', 'updated_at', 'deleted_at', 'remember_token', 'password'])
            ->toArray();
    }

    /**
     * Short human-readable identifier shown in the audit log
     * description, e.g. "SO-2026-001" instead of a bare row id.
     * Checked in field-priority order; falls back to "#{id}".
     */
    public function auditIdentifier(): string
    {
        foreach (['sales_order_no', 'customer_payment_tracking_no', 'reference_no', 'tracking_no', 'name', 'email'] as $field) {
            if (! empty($this->attributes[$field] ?? null)) {
                return (string) $this->attributes[$field];
            }
        }

        return '#'.$this->getKey();
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
