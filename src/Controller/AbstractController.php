<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Entity\Utilisateur;
use App\Repository\CategorieRepository;
use App\Repository\UtilisateurRepository;

/**
 * AbstractController — méthodes partagées par tous les contrôleurs.
 *
 * Centralise :
 *  - L'accès à l'utilisateur connecté
 *  - Le rendu de vue avec données contextuelles (flash, csrf, user)
 *  - Les redirections avec flash
 */
abstract class AbstractController
{
    protected function utilisateur(): ?Utilisateur
    {
        $id = Session::getUserId();
        if ($id === null) {
            return null;
        }
        $userRepo = new UtilisateurRepository(new CategorieRepository());
        return $userRepo->find($id);
    }

    protected function exigeUtilisateur(): Utilisateur
    {
        $u = $this->utilisateur();
        if ($u === null) {
            // En principe filtré par AuthMiddleware ; ceci est une sécurité.
            throw new \RuntimeException('Accès non authentifié.');
        }
        return $u;
    }

    /**
     * Rend un template avec les données fournies + données globales (user, flash, csrf).
     *
     * @param array<string, mixed> $data
     */
    protected function vue(string $template, array $data = []): Response
    {
        $data['utilisateurCourant'] = $this->utilisateur();
        $data['flash']              = Session::consumeFlash();
        $data['csrf']               = Session::csrfToken();
        $data['appName']            = APP_NAME;
        $data['footerCategories']   = (new CategorieRepository())->findAll();

        return Response::html(View::render($template, $data));
    }

    /**
     * Redirige avec un message flash.
     */
    protected function redirigerAvecFlash(string $url, string $type, string $message): Response
    {
        Session::flash($type, $message);
        return Response::redirect(url($url));
    }

    protected function rediriger(string $url): Response
    {
        return Response::redirect(url($url));
    }
}
