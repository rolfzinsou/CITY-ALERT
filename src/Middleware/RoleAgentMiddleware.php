<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Enum\Role;

/**
 * Restreint l'accès aux agents municipaux.
 */
final class RoleAgentMiddleware extends RoleMiddleware
{
    protected function roleAutorise(): Role
    {
        return Role::AGENT;
    }
}
