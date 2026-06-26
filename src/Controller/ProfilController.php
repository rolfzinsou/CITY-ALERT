<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Entity\Citoyen;
use App\Repository\CategorieRepository;
use App\Repository\SignalementRepository;
use App\Repository\UtilisateurRepository;

/**
 * ProfilController — espace personnel de l'utilisateur connecté.
 *
 *  - Affiche ses informations + ses signalements
 *  - Permet de modifier ses infos (nom, téléphone, notifications)
 *  - Permet de changer son mot de passe
 */
final class ProfilController extends AbstractController
{
    private UtilisateurRepository $userRepo;
    private SignalementRepository $signRepo;

    public function __construct()
    {
        $catRepo        = new CategorieRepository();
        $this->userRepo = new UtilisateurRepository($catRepo);
        $this->signRepo = new SignalementRepository($this->userRepo, $catRepo);
    }

    public function afficher(Request $request): Response
    {
        $user = $this->exigeUtilisateur();

        // Ses signalements
        $mesSignalements = $this->signRepo->rechercher([
            'auteurId' => $user->getId(),
        ]);

        // Stats personnelles
        $stats = [
            'total'    => count($mesSignalements),
            'resolus'  => count(array_filter($mesSignalements, fn($s) => $s->getStatut()->value === 'resolu')),
            'en_cours' => count(array_filter($mesSignalements, fn($s) => $s->getStatut()->value === 'en_cours')),
        ];

        return $this->vue('profil/index', [
            'user'            => $user,
            'mesSignalements' => $mesSignalements,
            'statsProfil'     => $stats,
        ]);
    }

    public function modifier(Request $request): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return $this->redirigerAvecFlash('/profil', 'error', 'Session expirée.');
        }

        $user = $this->exigeUtilisateur();

        $v = new Validator($request->post);
        $v->required('nom')->minLength('nom', 3)
          ->required('telephone')->telephoneSn('telephone');

        if ($v->fails()) {
            return $this->redirigerAvecFlash('/profil', 'error', 'Informations invalides.');
        }

        // Vérifier que le nouveau téléphone n'est pas pris par un autre
        $tel    = (string) $request->post('telephone');
        $autre  = $this->userRepo->findByTelephone($tel);
        if ($autre !== null && $autre->getId() !== $user->getId()) {
            return $this->redirigerAvecFlash('/profil', 'error', 'Ce numéro est déjà utilisé.');
        }

        $user->setNom((string) $request->post('nom'));
        $user->setTelephone($tel);
        $user->activerNotifications((bool) $request->post('notifications'));
        $this->userRepo->save($user);

        return $this->redirigerAvecFlash('/profil', 'success', 'Profil mis à jour.');
    }

    public function changerMotDePasse(Request $request): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return $this->redirigerAvecFlash('/profil', 'error', 'Session expirée.');
        }

        $user = $this->exigeUtilisateur();

        $ancien = (string) $request->post('ancien_mot_de_passe');
        if (!$user->verifierMotDePasse($ancien)) {
            return $this->redirigerAvecFlash('/profil', 'error', 'Ancien mot de passe incorrect.');
        }

        $v = new Validator($request->post);
        $v->required('nouveau_mot_de_passe')->minLength('nouveau_mot_de_passe', 8)
          ->sameAs('nouveau_mot_de_passe', 'confirmation', 'Les mots de passe ne correspondent pas');

        if ($v->fails()) {
            $first = $v->firstErrors();
            return $this->redirigerAvecFlash('/profil', 'error', reset($first) ?: 'Mot de passe invalide.');
        }

        $user->changerMotDePasse((string) $request->post('nouveau_mot_de_passe'));
        $this->userRepo->save($user);

        return $this->redirigerAvecFlash('/profil', 'success', 'Mot de passe modifié.');
    }
}
