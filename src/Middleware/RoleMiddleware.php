<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Enum\Role;
use App\Repository\CategorieRepository;
use App\Repository\UtilisateurRepository;

/**
 * RoleMiddleware — restreint l'accès à un rôle donné.
 *
 * Classe abstraite : on l'étend pour chaque rôle (RoleAdminMiddleware,
 * RoleAgentMiddleware) car le routeur instancie le middleware sans argument.
 */
abstract class RoleMiddleware
{
    abstract protected function roleAutorise(): Role;

    public function handle(Request $request): ?Response
    {
        $id = Session::getUserId();
        if ($id === null) {
            return Response::redirect(url('/login'));
        }

        $userRepo = new UtilisateurRepository(new CategorieRepository());
        $user     = $userRepo->find($id);

        if ($user === null || $user->getRole() !== $this->roleAutorise()) {
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Accès refusé'], 403);
            }
            return Response::forbidden();
        }

        return null;
    }
}
