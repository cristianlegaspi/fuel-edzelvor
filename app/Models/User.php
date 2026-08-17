<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, Notifiable;

    /**
     * Roles recognised by the app, from least to most privileged.
     * Keep this the single source of truth — controllers, policies,
     * middleware, and the user-management form all read from it so a
     * new role only has to be added in one place.
     */
    public const ROLE_STAFF = 'staff';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLES = [
        self::ROLE_STAFF => 'Staff',
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_SUPERADMIN => 'Super Admin',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * Note: 'role' and 'is_active' are deliberately fillable here, but
     * every place that mass-assigns them (UserController) must first
     * validate the incoming value against User::ROLES and gate the
     * request behind UserPolicy — never trust a client-supplied role
     * without that check, or this becomes a privilege-escalation hole.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Whether this user has one of the given roles.
     */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    /**
     * Admin-or-above check, used anywhere "can manage records but not
     * necessarily users" is the bar (e.g. deleting a sales order).
     */
    public function isAdminOrAbove(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN, self::ROLE_SUPERADMIN);
    }
}
