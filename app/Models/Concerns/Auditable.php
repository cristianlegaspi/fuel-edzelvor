<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            AuditLog::record('created', $model, [], static::auditableAttributes($model));
        });
        static::updated(function (Model $model): void {
            $changes = static::auditableAttributes($model, $model->getChanges());
            if (!$changes) return;
            $original = collect($changes)->keys()->mapWithKeys(
                fn ($key) => [$key => $model->getRawOriginal($key)]
            )->toArray();
            $reason = $model->auditEditReason ?? null;
            $description = $reason !== null
                ? 'Corrected '.class_basename($model).' '.(method_exists($model, 'auditIdentifier') ? $model->auditIdentifier() : '#'.$model->getKey()).' — Reason: '.$reason
                : null;
            AuditLog::record('updated', $model, $original, $changes, $description);
        });
        static::deleted(function (Model $model): void {
            $forceDeleted = method_exists($model, 'isForceDeleting') && $model->isForceDeleting();
            AuditLog::record($forceDeleted ? 'force_deleted' : 'deleted', $model, static::auditableAttributes($model));
        });
        if (method_exists(static::class, 'restored')) {
            static::restored(fn(Model $model) => AuditLog::record('restored', $model));
        }
    }
    protected static function auditableAttributes(Model $model, ?array $attributes = null): array
    {
        $attributes ??= $model->getAttributes();
        return collect($attributes)->except(['created_at','updated_at','deleted_at','remember_token','password'])->toArray();
    }
    public function auditIdentifier(): string
    {
        foreach (['sales_order_no','customer_payment_tracking_no','reference_no','tracking_no','name','email'] as $field) {
            if (!empty($this->attributes[$field] ?? null)) return (string) $this->attributes[$field];
        }
        return '#'.$this->getKey();
    }
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
