<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Entity\Agent;
use App\Entity\Citoyen;
use App\Repository\CategorieRepository;
use App\Repository\SignalementRepository;
use App\Repository\UtilisateurRepository;
use App\Service\CsvExportService;

/**
 * AdminController — tableau de bord administrateur.
 *
 * Responsabilités :
 *  - Stats globales
 *  - Gestion des comptes agents (création/édition avec affectation à une catégorie)
 *  - Vérification des pièces d'identité des citoyens
 *  - Export CSV (filtre catégorie possible)
 *
 * Toutes ces routes doivent être protégées par RoleAdminMiddleware.
 */
final class AdminController extends AbstractController
{
    private CategorieRepository $catRepo;
    private UtilisateurRepository $userRepo;
    private SignalementRepository $signRepo;

    public function __construct()
    {
        $this->catRepo  = new CategorieRepository();
        $this->userRepo = new UtilisateurRepository($this->catRepo);
        $this->signRepo = new SignalementRepository($this->userRepo, $this->catRepo);
    }

    // ============================================================
    //  Tableau de bord
    // ============================================================

    public function dashboard(Request $request): Response
    {
        $statsStatut    = $this->signRepo->statistiquesParStatut();
        $statsCategorie = $this->signRepo->statistiquesParCategorie();

        // Calcul du délai moyen et autres KPI utiles
        $pdo  = Database::getInstance();
        $delai = $pdo->query(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, s.date_creation, h.date_modification) / 24) AS jours
             FROM signalements s
             JOIN historiques_statut h ON h.signalement_id = s.id
             WHERE h.nouveau_statut = 'resolu'"
        )->fetchColumn();

        $derniersSignalements = $this->signRepo->rechercher(['limit' => 10]);

        return $this->vue('dashboard/admin', [
            'statsStatut'          => $statsStatut,
            'statsCategorie'       => $statsCategorie,
            'categories'           => $this->catRepo->findAll(),
            'derniersSignalements' => $derniersSignalements,
            'delaiMoyenJours'      => $delai !== false ? round((float) $delai, 1) : null,
            'totalCitoyens'        => $this->compterParRole('citoyen'),
            'totalAgents'          => $this->compterParRole('agent'),
        ]);
    }

    // ============================================================
    //  Gestion des agents (création + affectation catégorie)
    // ============================================================

    public function listeAgents(Request $request): Response
    {
        $pdo  = Database::getInstance();
        $rows = $pdo->query(
            "SELECT u.id, u.nom, u.email, u.telephone, u.service, c.id AS cat_id, c.nom AS cat_nom
             FROM utilisateurs u
             LEFT JOIN agent_categorie ac ON ac.agent_id = u.id
             LEFT JOIN categories c       ON c.id = ac.categorie_id
             WHERE u.role = 'agent'
             ORDER BY u.nom"
        )->fetchAll();

        return $this->vue('dashboard/agents', [
            'agents'     => $rows,
            'categories' => $this->catRepo->findAll(),
        ]);
    }

    public function creerAgent(Request $request): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return $this->redirigerAvecFlash('/admin/agents', 'error', 'Session expirée.');
        }

        $v = new Validator($request->post);
        $v->required('nom')->minLength('nom', 3)
          ->required('email')->email('email')
          ->required('telephone')->telephoneSn('telephone')
          ->required('mot_de_passe')->minLength('mot_de_passe', 8)
          ->required('service')
          ->required('categorie_id');

        if ($v->fails()) {
            return $this->vue('dashboard/agents', [
                'erreurs'    => $v->firstErrors(),
                'old'        => $request->post,
                'categories' => $this->catRepo->findAll(),
                'agents'     => [],
            ]);
        }

        if ($this->userRepo->findByEmail((string) $request->post('email')) !== null) {
            return $this->redirigerAvecFlash('/admin/agents', 'error', 'Email déjà utilisé.');
        }
        if ($this->userRepo->findByTelephone((string) $request->post('telephone')) !== null) {
            return $this->redirigerAvecFlash('/admin/agents', 'error', 'Téléphone déjà utilisé.');
        }

        $categorie = $this->catRepo->find((int) $request->post('categorie_id'));
        if ($categorie === null) {
            return $this->redirigerAvecFlash('/admin/agents', 'error', 'Catégorie invalide.');
        }

        $agent = new Agent(
            nom:        (string) $request->post('nom'),
            email:      (string) $request->post('email'),
            motDePasse: password_hash((string) $request->post('mot_de_passe'), PASSWORD_DEFAULT),
            telephone:  (string) $request->post('telephone'),
            service:    (string) $request->post('service'),
            categorie:  $categorie,
        );
        $this->userRepo->save($agent);

        return $this->redirigerAvecFlash(
            '/admin/agents',
            'success',
            "Agent {$agent->getNom()} créé et affecté à « {$categorie->getNom()} »."
        );
    }

    public function montrerEditionAgent(Request $request, string $id): Response
    {
        $agent = $this->userRepo->find((int) $id);
        if (!$agent instanceof Agent) {
            return Response::notFound();
        }
        return $this->vue('dashboard/agents_editer', [
            'agent'      => $agent,
            'categories' => $this->catRepo->findAll(),
        ]);
    }

    public function modifierAgent(Request $request, string $id): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return $this->redirigerAvecFlash('/admin/agents', 'error', 'Session expirée.');
        }

        $agent = $this->userRepo->find((int) $id);
        if (!$agent instanceof Agent) {
            return Response::notFound();
        }

        $v = new Validator($request->post);
        $v->required('nom')->minLength('nom', 3)
          ->required('email')->email('email')
          ->required('telephone')->telephoneSn('telephone')
          ->required('service')
          ->required('categorie_id');

        $mdp = (string) $request->post('mot_de_passe');
        if ($mdp !== '') {
            $v->minLength('mot_de_passe', 8);
        }

        if ($v->fails()) {
            return $this->vue('dashboard/agents_editer', [
                'erreurs'    => $v->firstErrors(),
                'old'        => $request->post,
                'agent'      => $agent,
                'categories' => $this->catRepo->findAll(),
            ]);
        }

        $emailExist = $this->userRepo->findByEmail((string) $request->post('email'));
        if ($emailExist !== null && $emailExist->getId() !== $agent->getId()) {
            return $this->redirigerAvecFlash('/admin/agents/' . $id . '/editer', 'error', 'Email déjà utilisé.');
        }
        $telExist = $this->userRepo->findByTelephone((string) $request->post('telephone'));
        if ($telExist !== null && $telExist->getId() !== $agent->getId()) {
            return $this->redirigerAvecFlash('/admin/agents/' . $id . '/editer', 'error', 'Téléphone déjà utilisé.');
        }

        $categorie = $this->catRepo->find((int) $request->post('categorie_id'));
        if ($categorie === null) {
            return $this->redirigerAvecFlash('/admin/agents/' . $id . '/editer', 'error', 'Catégorie invalide.');
        }

        $agent->setNom((string) $request->post('nom'));
        $agent->setEmail((string) $request->post('email'));
        $agent->setTelephone((string) $request->post('telephone'));
        $agent->setService((string) $request->post('service'));
        $agent->setCategorie($categorie);
        if ($mdp !== '') {
            $agent->setMotDePasseHash(password_hash($mdp, PASSWORD_DEFAULT));
        }

        $this->userRepo->save($agent);

        return $this->redirigerAvecFlash('/admin/agents', 'success', "Agent {$agent->getNom()} modifié.");
    }

    public function supprimerAgent(Request $request, string $id): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return $this->redirigerAvecFlash('/admin/agents', 'error', 'Session expirée.');
        }

        $agent = $this->userRepo->find((int) $id);
        if (!$agent instanceof Agent) {
            return Response::notFound();
        }

        $nom = $agent->getNom();
        $this->userRepo->delete((int) $id);

        return $this->redirigerAvecFlash('/admin/agents', 'success', "Agent {$nom} supprimé.");
    }

    // ============================================================
    //  Vérification des pièces d'identité des citoyens
    // ============================================================

    public function citoyensAVerifier(Request $request): Response
    {
        $pdo  = Database::getInstance();
        $rows = $pdo->query(
            "SELECT id, nom, email, telephone, piece_identite_recto, piece_identite_verso, date_inscription
             FROM utilisateurs
             WHERE role = 'citoyen'
               AND verifie = 0
               AND piece_identite_recto IS NOT NULL
             ORDER BY date_inscription ASC"
        )->fetchAll();

        return $this->vue('dashboard/verifications', ['citoyens' => $rows]);
    }

    public function verifierCitoyen(Request $request, string $id): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return Response::forbidden();
        }
        $user = $this->userRepo->find((int) $id);
        if (!$user instanceof Citoyen) {
            return Response::notFound();
        }
        $user->setVerifie(true);
        $this->userRepo->save($user);
        return $this->redirigerAvecFlash('/admin/verifications', 'success', 'Citoyen vérifié.');
    }

    // ============================================================
    //  Export CSV (bonus du sujet) — avec filtre catégorie
    // ============================================================

    public function exporterCsv(Request $request): Response
    {
        $service = new CsvExportService($this->signRepo);
        $categorieId = $request->get('categorie') !== null ? (int) $request->get('categorie') : null;

        $csv     = $service->exporter($categorieId);
        $nomFich = $service->nomFichier($categorieId);

        return (new Response($csv, 200))
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $nomFich . '"');
    }

    // ---------- Helpers ----------

    private function compterParRole(string $role): int
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT COUNT(*) FROM utilisateurs WHERE role = :r'
        );
        $stmt->execute(['r' => $role]);
        return (int) $stmt->fetchColumn();
    }
}
