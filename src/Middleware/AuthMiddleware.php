<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * AuthMiddleware — bloque l'accès si l'utilisateur n'est pas connecté.
 *
 * Renvoie :
 *  - 401 JSON si requête AJAX/JSON
 *  - Redirect /login sinon
 */
final class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (Session::getUserId() !== null) {
            return null;   // OK, on laisse passer
        }

        if ($request->expectsJson()) {
            return Response::json(['error' => 'Non authentifié'], 401);
        }

        Session::flash('info', 'Connectez-vous pour accéder à cette page.');
        return Response::redirect(url('/login'));
    }
}
