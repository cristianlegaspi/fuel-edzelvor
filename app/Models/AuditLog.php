<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * A single append-only entry in the activity trail. Rows are written
 * by App\Models\Concerns\Auditable (for model create/update/delete/
 * restore) and directly by AuthController (for login/logout, which
 * have no model instance to hang an Eloquent event off of).
 *
 * Nothing in the app ever updates or deletes a row here — the whole
 * point is that it stays a faithful record of what happened.
 */
class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Every action this app writes to the trail, mapped to its display
     * label. Keep this the single source of truth — the filter dropdown
     * and the badge colours on the audit log page both read from it.
     */
    public const ACTIONS = [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
        'force_deleted' => 'Permanently Deleted',
        'login' => 'Logged In',
        'logout' => 'Logged Out',
        'login_failed' => 'Failed Login',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? Str::headline($this->action);
    }

    public function actionColor(): string
    {
        return match ($this->action) {
            'created', 'login' => 'green',
            'updated' => 'blue',
            'restored' => 'purple',
            'deleted', 'force_deleted', 'login_failed' => 'red',
            default => 'gray',
        };
    }

    /**
     * Write an audit entry.
     *
     * $subject is the affected model (omit for login/logout, which
     * aren't tied to a specific record). $actorId overrides "whoever
     * is currently authenticated" for the rare case the acting user has
     * already been logged out by the time we record the event (e.g. a
     * deactivated-account login attempt).
     */
    public static function record(
        string $action,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null,
        ?int $actorId = null,
    ): self {
        $request = request();

        return static::create([
            'user_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'description' => $description ?? static::defaultDescription($action, $subject),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
        ]);
    }

    protected static function defaultDescription(string $action, ?Model $subject): string
    {
        $verb = self::ACTIONS[$action] ?? Str::headline($action);

        if (! $subject) {
            return $verb;
        }

        $label = Str::headline(class_basename($subject));

        $identifier = method_exists($subject, 'auditIdentifier')
            ? $subject->auditIdentifier()
            : '#'.$subject->getKey();

        return "{$verb} {$label} {$identifier}";
    }
}
