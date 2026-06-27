<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Entity\Agent;
use App\Enum\Statut;
use App\Repository\CategorieRepository;
use App\Repository\HistoriqueStatutRepository;
use App\Repository\SignalementRepository;
use App\Repository\UtilisateurRepository;
use App\Service\NotificationService;
use DomainException;

/**
 * AgentController — vue agent municipal.
 *
 * Règles métier :
 *  - Un agent ne voit que les signalements de SA catégorie
 *  - Un agent peut changer le statut (avec remarque) selon les transitions autorisées
 *  - Chaque changement déclenche une entrée d'historique + notification email à l'auteur
 *
 * L'accès à ces routes doit être protégé par RoleAgentMiddleware côté router.
 */
final class AgentController extends AbstractController
{
    private SignalementRepository $signRepo;
    private HistoriqueStatutRepository $histoRepo;
    private NotificationService $notif;

    public function __construct()
    {
        $catRepo         = new CategorieRepository();
        $userRepo        = new UtilisateurRepository($catRepo);
        $this->signRepo  = new SignalementRepository($userRepo, $catRepo);
        $this->histoRepo = new HistoriqueStatutRepository($userRepo, $this->signRepo);
        $this->notif     = new NotificationService();
    }

    /**
     * Liste des signalements de la catégorie de l'agent connecté.
     */
    public function mesSignalements(Request $request): Response
    {
        $agent = $this->exigeUtilisateur();
        if (!$agent instanceof Agent || $agent->getCategorie() === null) {
            return $this->redirigerAvecFlash('/', 'error', 'Compte agent non configuré.');
        }

        $filtres = ['categorieId' => $agent->getCategorie()->getId()];
        if ($s = $request->get('statut')) {
            $filtres['statut'] = Statut::tryFrom((string) $s);
        }

        $signalements = $this->signRepo->rechercher(array_filter($filtres));

        return $this->vue('dashboard/agent', [
            'agent'        => $agent,
            'categorie'    => $agent->getCategorie(),
            'signalements' => $signalements,
            'statuts'      => Statut::cases(),
        ]);
    }

    /**
     * Change le statut d'un signalement. La règle métier (transition autorisée,
     * agent habilité pour la catégorie) est encodée dans Signalement::changerStatut().
     */
    public function changerStatut(Request $request, string $id): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return $this->redirigerAvecFlash('/agent/signalements', 'error', 'Session expirée.');
        }

        $signalement = $this->signRepo->find((int) $id);
        if ($signalement === null) {
            return Response::notFound();
        }

        $agent = $this->exigeUtilisateur();
        if (!$agent instanceof Agent) {
            return Response::forbidden();
        }

        $nouveau = Statut::tryFrom((string) $request->post('nouveau_statut'));
        if ($nouveau === null) {
            return $this->redirigerAvecFlash('/agent/signalements', 'error', 'Statut invalide.');
        }

        $remarque = $request->post('remarque') ? trim((string) $request->post('remarque')) : null;
        if ($remarque === '') {
            $remarque = null;
        }

        try {
            $historique = $signalement->changerStatut($nouveau, $agent, $remarque);
            $this->signRepo->save($signalement);
            $this->histoRepo->save($historique);

            // Bonus du sujet : notification email à l'auteur
            $this->notif->notifierChangementStatut($signalement, $historique);

        } catch (DomainException $e) {
            return $this->redirigerAvecFlash(
                '/signalements/' . $id,
                'error',
                $e->getMessage()
            );
        }

        return $this->redirigerAvecFlash(
            '/signalements/' . $id,
            'success',
            'Statut mis à jour. L\'auteur a été notifié.'
        );
    }
}
