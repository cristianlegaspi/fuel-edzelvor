<?php

namespace App\Policies;

use App\Models\User;

/**
 * Backs UserController. The 'role:superadmin' route middleware already
 * keeps non-superadmins out of /users entirely, but the specific
 * self-protection rules below (don't lock yourself out, don't strand the
 * app with zero superadmins) live here so they're enforced no matter how
 * the action is invoked.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isSuperAdmin();
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isSuperAdmin();
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin();
    }

    /**
     * A superadmin may deactivate/demote anyone except themselves —
     * self-service role changes are how people accidentally (or
     * maliciously, via a stolen session) lock everyone else out or
     * quietly de-escalate audit trails.
     */
    public function deactivate(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin() && $actor->isNot($target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin() && $actor->isNot($target);
    }
}
