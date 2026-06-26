<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Entity\Photo;
use App\Entity\Signalement;
use App\Repository\CategorieRepository;
use App\Repository\CommentaireRepository;
use App\Repository\HistoriqueStatutRepository;
use App\Repository\PhotoRepository;
use App\Repository\SignalementRepository;
use App\Repository\UtilisateurRepository;
use App\Service\NotificationService;
use App\Service\UploadService;

/**
 * SignalementController — création, affichage, modification, suppression.
 *
 * Règle métier centrale (sujet) :
 * « Modification et suppression par leur auteur tant que le statut est « Nouveau » »
 * → encodée dans Signalement::peutEtreModifiePar() / peutEtreSupprimePar().
 *
 * Toute personne connectée peut créer un signalement.
 * Toute personne peut consulter (publique).
 */
final class SignalementController extends AbstractController
{
    private SignalementRepository $signRepo;
    private CategorieRepository $catRepo;
    private PhotoRepository $photoRepo;
    private CommentaireRepository $commRepo;
    private HistoriqueStatutRepository $histoRepo;
    private UploadService $uploadService;
    private NotificationService $notif;

    public function __construct()
    {
        $this->catRepo       = new CategorieRepository();
        $userRepo            = new UtilisateurRepository($this->catRepo);
        $this->signRepo      = new SignalementRepository($userRepo, $this->catRepo);
        $this->photoRepo     = new PhotoRepository($this->signRepo);
        $this->commRepo      = new CommentaireRepository($userRepo, $this->signRepo);
        $this->histoRepo     = new HistoriqueStatutRepository($userRepo, $this->signRepo);
        $this->uploadService = new UploadService();
        $this->notif         = new NotificationService();
    }

    // ============================================================
    //  Lecture
    // ============================================================

    public function afficher(Request $request, string $id): Response
    {
        $signalement = $this->signRepo->find((int) $id);
        if ($signalement === null) {
            return Response::notFound('Signalement introuvable');
        }

        // Hydrate les photos, commentaires, historique
        $signalement->setPhotos($this->photoRepo->findParSignalement((int) $id));
        $signalement->setCommentaires($this->commRepo->findRacinesParSignalement((int) $id));
        $historique = $this->histoRepo->findParSignalement((int) $id);

        // L'utilisateur connecté a-t-il déjà soutenu ?
        $userCourant = $this->utilisateur();
        $aSoutenu    = $userCourant !== null
            && $this->signRepo->aSoutenu((int) $id, (int) $userCourant->getId());

        return $this->vue('signalement/show', [
            'signalement' => $signalement,
            'historique'  => $historique,
            'aSoutenu'    => $aSoutenu,
            'useLeaflet'  => true,
        ]);
    }

    // ============================================================
    //  Création
    // ============================================================

    public function montrerFormulaire(Request $request): Response
    {
        return $this->vue('signalement/create', [
            'categories' => $this->catRepo->findAll(),
        ]);
    }

    public function creer(Request $request): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return $this->redirigerAvecFlash('/signalements/nouveau', 'error', 'Session expirée.');
        }

        $v = new Validator($request->post);
        $v->required('titre')->minLength('titre', 10)->maxLength('titre', 200)
          ->required('description')->minLength('description', 20)
          ->required('adresse')
          ->required('categorie_id');

        if ($v->fails()) {
            return $this->vue('signalement/create', [
                'categories' => $this->catRepo->findAll(),
                'erreurs'    => $v->firstErrors(),
                'old'        => $request->post,
            ]);
        }

        $categorie = $this->catRepo->find((int) $request->post('categorie_id'));
        if ($categorie === null) {
            return $this->redirigerAvecFlash('/signalements/nouveau', 'error', 'Catégorie invalide.');
        }

        $signalement = new Signalement(
            titre:       (string) $request->post('titre'),
            description: (string) $request->post('description'),
            adresse:     (string) $request->post('adresse'),
            auteur:      $this->exigeUtilisateur(),
            categorie:   $categorie,
            latitude:    $request->post('latitude')  !== null ? (float) $request->post('latitude')  : null,
            longitude:   $request->post('longitude') !== null ? (float) $request->post('longitude') : null,
        );
        $this->signRepo->save($signalement);

        // Notifie les agents de la catégorie
        $userRepo = new UtilisateurRepository($this->catRepo);
        $this->notif->notifierNouveauSignalement($signalement, $userRepo);

        // Upload des photos (champ <input name="photos[]" multiple>)
        $files = $request->file('photos');
        if ($files !== null && !empty($files['name'])) {
            try {
                $chemins = $this->uploadService->gererUploadPhotosMultiple($files, 'signalements');
                foreach ($chemins as $i => $chemin) {
                    $photo = new Photo($chemin, $signalement, $i);
                    $this->photoRepo->save($photo);
                }
            } catch (\RuntimeException $e) {
                // Photos ratées mais signalement créé → on prévient sans bloquer
                Session::flash('warning', "Signalement créé, mais erreur photos : {$e->getMessage()}");
            }
        }

        return $this->redirigerAvecFlash(
            '/signalements/' . $signalement->getId(),
            'success',
            'Signalement publié ! Merci pour votre contribution.'
        );
    }

    // ============================================================
    //  Modification — uniquement si statut = Nouveau ET auteur
    // ============================================================

    public function montrerEdition(Request $request, string $id): Response
    {
        $signalement = $this->signRepo->find((int) $id);
        if ($signalement === null) {
            return Response::notFound();
        }
        $user = $this->exigeUtilisateur();
        if (!$signalement->peutEtreModifiePar($user)) {
            return $this->redirigerAvecFlash(
                '/signalements/' . $id,
                'error',
                "Ce signalement ne peut plus être modifié (statut actuel : "
                . $signalement->getStatut()->libelle() . ")."
            );
        }
        return $this->vue('signalement/edit', [
            'signalement' => $signalement,
            'categories'  => $this->catRepo->findAll(),
        ]);
    }

    public function modifier(Request $request, string $id): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return $this->redirigerAvecFlash('/signalements/' . $id, 'error', 'Session expirée.');
        }

        $signalement = $this->signRepo->find((int) $id);
        if ($signalement === null) {
            return Response::notFound();
        }
        $user = $this->exigeUtilisateur();
        if (!$signalement->peutEtreModifiePar($user)) {
            return $this->redirigerAvecFlash(
                '/signalements/' . $id,
                'error',
                "Modification impossible : statut « " . $signalement->getStatut()->libelle() . " »."
            );
        }

        $signalement->setTitre((string) $request->post('titre'));
        $signalement->setDescription((string) $request->post('description'));
        $signalement->setAdresse((string) $request->post('adresse'));
        if ($cid = $request->post('categorie_id')) {
            $cat = $this->catRepo->find((int) $cid);
            if ($cat) $signalement->setCategorie($cat);
        }
        $this->signRepo->save($signalement);

        return $this->redirigerAvecFlash(
            '/signalements/' . $id,
            'success',
            'Signalement mis à jour.'
        );
    }

    // ============================================================
    //  Suppression — uniquement si statut = Nouveau ET auteur
    // ============================================================

    public function supprimer(Request $request, string $id): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return $this->redirigerAvecFlash('/signalements/' . $id, 'error', 'Session expirée.');
        }

        $signalement = $this->signRepo->find((int) $id);
        if ($signalement === null) {
            return Response::notFound();
        }
        $user = $this->exigeUtilisateur();
        if (!$signalement->peutEtreSupprimePar($user)) {
            return $this->redirigerAvecFlash(
                '/signalements/' . $id,
                'error',
                "Suppression impossible : statut « " . $signalement->getStatut()->libelle() . " »."
            );
        }

        $this->signRepo->delete((int) $id);
        return $this->redirigerAvecFlash('/', 'success', 'Signalement supprimé.');
    }

    // ============================================================
    //  Soutiens (« like » sur signalement)
    // ============================================================

    public function basculerSoutien(Request $request, string $id): Response
    {
        $user = $this->exigeUtilisateur();
        $sid  = (int) $id;

        if ($this->signRepo->aSoutenu($sid, (int) $user->getId())) {
            $this->signRepo->retirerSoutien($sid, (int) $user->getId());
            $action = 'retire';
        } else {
            $this->signRepo->ajouterSoutien($sid, (int) $user->getId());
            $action = 'ajoute';
        }

        $total = $this->signRepo->compterSoutiens($sid);
        return Response::json(['action' => $action, 'total' => $total]);
    }
}
