<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Enum\Role;

/**
 * Restreint l'accès aux administrateurs.
 */
final class RoleAdminMiddleware extends RoleMiddleware
{
    protected function roleAutorise(): Role
    {
        return Role::ADMINISTRATEUR;
    }
}
