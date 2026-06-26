<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Entity\Commentaire;
use App\Repository\CategorieRepository;
use App\Repository\CommentaireRepository;
use App\Repository\SignalementRepository;
use App\Repository\UtilisateurRepository;

/**
 * CommentaireController — commentaires sous les signalements.
 *
 * Supporte :
 *  - Commentaires racines + réponses (parent_id)
 *  - Likes via table d'association
 */
final class CommentaireController extends AbstractController
{
    private CommentaireRepository $commRepo;
    private SignalementRepository $signRepo;

    public function __construct()
    {
        $catRepo        = new CategorieRepository();
        $userRepo       = new UtilisateurRepository($catRepo);
        $this->signRepo = new SignalementRepository($userRepo, $catRepo);
        $this->commRepo = new CommentaireRepository($userRepo, $this->signRepo);
    }

    public function publier(Request $request, string $signalementId): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return $this->redirigerAvecFlash('/signalements/' . $signalementId, 'error', 'Session expirée.');
        }

        $v = new Validator($request->post);
        $v->required('contenu')->minLength('contenu', 2)->maxLength('contenu', 2000);
        if ($v->fails()) {
            return $this->redirigerAvecFlash(
                '/signalements/' . $signalementId,
                'error',
                $v->firstErrors()['contenu'] ?? 'Commentaire invalide.'
            );
        }

        $signalement = $this->signRepo->find((int) $signalementId);
        if ($signalement === null) {
            return Response::notFound();
        }

        $parent = null;
        if ($parentId = $request->post('parent_id')) {
            $parent = $this->commRepo->find((int) $parentId);
            // Sécurité : le parent doit appartenir au même signalement
            if ($parent !== null && $parent->getSignalement()->getId() !== $signalement->getId()) {
                $parent = null;
            }
        }

        $commentaire = new Commentaire(
            contenu:     (string) $request->post('contenu'),
            auteur:      $this->exigeUtilisateur(),
            signalement: $signalement,
            parent:      $parent,
        );
        $this->commRepo->save($commentaire);

        return $this->rediriger('/signalements/' . $signalementId . '#commentaire-' . $commentaire->getId());
    }

    public function supprimer(Request $request, string $id): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return Response::forbidden('CSRF invalide.');
        }

        $commentaire = $this->commRepo->find((int) $id);
        if ($commentaire === null) {
            return Response::notFound();
        }
        $user = $this->exigeUtilisateur();
        // Seul l'auteur peut supprimer son commentaire
        if ($commentaire->getAuteur()->getId() !== $user->getId()) {
            return Response::forbidden();
        }

        $signalementId = $commentaire->getSignalement()->getId();
        $this->commRepo->delete((int) $id);
        return $this->redirigerAvecFlash(
            '/signalements/' . $signalementId,
            'success',
            'Commentaire supprimé.'
        );
    }

    /**
     * Bascule le like sur un commentaire (toggle). Retourne le nouveau total en JSON.
     */
    public function basculerLike(Request $request, string $id): Response
    {
        if (!Session::verifyCsrf($request->csrfToken())) {
            return Response::json(['error' => 'CSRF invalide'], 403);
        }

        $user = $this->exigeUtilisateur();
        $cid  = (int) $id;

        if ($this->commRepo->aLike($cid, (int) $user->getId())) {
            $this->commRepo->unliker($cid, (int) $user->getId());
            $action = 'retire';
        } else {
            $this->commRepo->liker($cid, (int) $user->getId());
            $action = 'ajoute';
        }

        $total = $this->commRepo->compterLikes($cid);
        return Response::json(['action' => $action, 'total' => $total]);
    }
}
