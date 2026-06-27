<?php
/**
 * config/routes.php
 *
 * Définition de toutes les routes. Renvoie un Router prêt à dispatcher.
 *
 * Convention :
 *   - GET  pour afficher
 *   - POST pour modifier/créer
 *   - Suppressions via POST avec champ caché (HTML ne supporte pas DELETE)
 */

use App\Core\Router;
use App\Controller\AdminController;
use App\Controller\AgentController;
use App\Controller\ApiController;
use App\Controller\AuthController;
use App\Controller\CommentaireController;
use App\Controller\HomeController;
use App\Controller\ProfilController;
use App\Controller\SignalementController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleAdminMiddleware;
use App\Middleware\RoleAgentMiddleware;

$router = new Router();

// ============================================================
//  Routes publiques
// ============================================================
$router->get('/',                            [HomeController::class, 'index']);

// API publique (lecture seule, bonus du sujet)
$router->get('/api/signalements',            [ApiController::class, 'signalements']);
$router->get('/api/signalements/{id}',       [ApiController::class, 'signalement']);
$router->get('/api/categories',              [ApiController::class, 'categories']);
$router->get('/api/stats',                   [ApiController::class, 'stats']);

// ============================================================
//  Authentification
// ============================================================
$router->get('/login',                       [AuthController::class, 'montrerLogin']);
$router->post('/login',                      [AuthController::class, 'traiterLogin']);
$router->get('/register',                    [AuthController::class, 'montrerRegister']);
$router->post('/register',                   [AuthController::class, 'traiterRegister']);
$router->post('/logout',                     [AuthController::class, 'deconnecter'])
       ->middleware(AuthMiddleware::class);

// ============================================================
//  Profil utilisateur
// ============================================================
$router->get('/profil',                      [ProfilController::class, 'afficher'])
       ->middleware(AuthMiddleware::class);
$router->post('/profil',                     [ProfilController::class, 'modifier'])
       ->middleware(AuthMiddleware::class);
$router->post('/profil/mot-de-passe',        [ProfilController::class, 'changerMotDePasse'])
       ->middleware(AuthMiddleware::class);

// ============================================================
//  Signalements — actions protégées
// ============================================================
$router->get('/signalements/nouveau',        [SignalementController::class, 'montrerFormulaire'])
       ->middleware(AuthMiddleware::class);

$router->post('/signalements',               [SignalementController::class, 'creer'])
       ->middleware(AuthMiddleware::class);

$router->get('/signalements/{id}/editer',    [SignalementController::class, 'montrerEdition'])
       ->middleware(AuthMiddleware::class);

$router->post('/signalements/{id}/editer',   [SignalementController::class, 'modifier'])
       ->middleware(AuthMiddleware::class);

$router->post('/signalements/{id}/supprimer',[SignalementController::class, 'supprimer'])
       ->middleware(AuthMiddleware::class);

$router->post('/signalements/{id}/soutenir', [SignalementController::class, 'basculerSoutien'])
       ->middleware(AuthMiddleware::class);

// Route avec paramètre {id} EN DERNIER : sinon elle attrape "nouveau", "editer", etc.
$router->get('/signalements/{id}',           [SignalementController::class, 'afficher']);

// ============================================================
//  Commentaires
// ============================================================
$router->post('/signalements/{signalementId}/commentaires',
              [CommentaireController::class, 'publier'])
       ->middleware(AuthMiddleware::class);

$router->post('/commentaires/{id}/supprimer',
              [CommentaireController::class, 'supprimer'])
       ->middleware(AuthMiddleware::class);

$router->post('/commentaires/{id}/like',
              [CommentaireController::class, 'basculerLike'])
       ->middleware(AuthMiddleware::class);

// ============================================================
//  Espace agent (sa catégorie uniquement)
// ============================================================
$router->get('/agent/signalements',          [AgentController::class, 'mesSignalements'])
       ->middleware(AuthMiddleware::class)
       ->middleware(RoleAgentMiddleware::class);

$router->post('/agent/signalements/{id}/statut',
              [AgentController::class, 'changerStatut'])
       ->middleware(AuthMiddleware::class)
       ->middleware(RoleAgentMiddleware::class);

// ============================================================
//  Espace admin
// ============================================================
$router->get('/dashboard',                   [AdminController::class, 'dashboard'])
       ->middleware(AuthMiddleware::class)
       ->middleware(RoleAdminMiddleware::class);

$router->get('/admin/agents',                [AdminController::class, 'listeAgents'])
       ->middleware(AuthMiddleware::class)
       ->middleware(RoleAdminMiddleware::class);

$router->post('/admin/agents',               [AdminController::class, 'creerAgent'])
       ->middleware(AuthMiddleware::class)
       ->middleware(RoleAdminMiddleware::class);

$router->get('/admin/agents/{id}/editer',    [AdminController::class, 'montrerEditionAgent'])
       ->middleware(AuthMiddleware::class)
       ->middleware(RoleAdminMiddleware::class);

$router->post('/admin/agents/{id}/editer',   [AdminController::class, 'modifierAgent'])
       ->middleware(AuthMiddleware::class)
       ->middleware(RoleAdminMiddleware::class);

$router->post('/admin/agents/{id}/supprimer',[AdminController::class, 'supprimerAgent'])
       ->middleware(AuthMiddleware::class)
       ->middleware(RoleAdminMiddleware::class);

$router->get('/admin/verifications',         [AdminController::class, 'citoyensAVerifier'])
       ->middleware(AuthMiddleware::class)
       ->middleware(RoleAdminMiddleware::class);

$router->post('/admin/citoyens/{id}/verifier',
              [AdminController::class, 'verifierCitoyen'])
       ->middleware(AuthMiddleware::class)
       ->middleware(RoleAdminMiddleware::class);

$router->get('/admin/export.csv',            [AdminController::class, 'exporterCsv'])
       ->middleware(AuthMiddleware::class)
       ->middleware(RoleAdminMiddleware::class);

return $router;