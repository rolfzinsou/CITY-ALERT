<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repository\CategorieRepository;
use App\Repository\UtilisateurRepository;
use App\Service\AuthService;
use App\Service\UploadService;
use DomainException;

/**
 * AuthController — connexion / inscription / déconnexion.
 *
 * Routes typiques :
 *  GET  /login       → formulaire de connexion
 *  POST /login       → traitement
 *  GET  /register    → formulaire d'inscription (citoyen uniquement)
 *  POST /register    → traitement
 *  POST /logout      → déconnexion
 */
final class AuthController extends AbstractController
{
    private AuthService $auth;
    private UploadService $uploadService;

    public function __construct()
    {
        $userRepo            = new UtilisateurRepository(new CategorieRepository());
        $this->auth          = new AuthService($userRepo);
        $this->uploadService = new UploadService();
    }

    // ---------- LOGIN ----------

    public function montrerLogin(Request $request): Response
    {
        if ($this->utilisateur() !== null) {
            return $this->rediriger('/');
        }
        return $this->vue('auth/login');
    }

    public function traiterLogin(Request $request): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return $this->redirigerAvecFlash('/login', 'error', 'Session expirée, réessayez.');
        }

        $v = new Validator($request->post);
        $v->required('identifiant', 'Email ou téléphone requis')
          ->required('mot_de_passe', 'Mot de passe requis');

        if ($v->fails()) {
            return $this->vue('auth/login', [
                'erreurs' => $v->firstErrors(),
                'old'     => ['identifiant' => $request->post('identifiant')],
            ]);
        }

        $user = $this->auth->connecter(
            identifiant:      (string) $request->post('identifiant'),
            motDePasseClair:  (string) $request->post('mot_de_passe'),
            sesouvenir:       (bool) $request->post('se_souvenir'),
        );

        if ($user === null) {
            return $this->vue('auth/login', [
                'erreurs' => ['identifiant' => 'Identifiants invalides'],
                'old'     => ['identifiant' => $request->post('identifiant')],
            ]);
        }

        // Redirection selon le rôle
        $dest = match ($user->getRole()->value) {
            'administrateur' => '/dashboard',
            'agent'          => '/agent/signalements',
            default          => '/',
        };
        return $this->redirigerAvecFlash($dest, 'success', 'Connexion réussie !');
    }

    // ---------- REGISTER ----------

    public function montrerRegister(Request $request): Response
    {
        if ($this->utilisateur() !== null) {
            return $this->rediriger('/');
        }
        return $this->vue('auth/register');
    }

    public function traiterRegister(Request $request): Response
    {
        if (!Session::verifyCsrf((string) $request->post('csrf'))) {
            return $this->redirigerAvecFlash('/register', 'error', 'Session expirée.');
        }

        $v = new Validator($request->post);
        $v->required('nom')->minLength('nom', 3)->maxLength('nom', 100)
          ->required('email')->email('email')
          ->required('telephone')->telephoneSn('telephone')
          ->required('mot_de_passe')->minLength('mot_de_passe', 8)
          ->required('mot_de_passe_confirmation')
          ->sameAs('mot_de_passe', 'mot_de_passe_confirmation', 'Les mots de passe ne correspondent pas');

        if ($v->fails()) {
            return $this->vue('auth/register', [
                'erreurs' => $v->firstErrors(),
                'old'     => $request->post,
            ]);
        }

        // Upload optionnel des pièces d'identité
        $pieceRecto = null;
        $pieceVerso = null;
        try {
            $recto = $request->file('piece_recto');
            if ($recto !== null && ($recto['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $pieceRecto = $this->uploadService->gererUploadPhoto($recto, 'pieces');
            }
            $verso = $request->file('piece_verso');
            if ($verso !== null && ($verso['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $pieceVerso = $this->uploadService->gererUploadPhoto($verso, 'pieces');
            }
        } catch (\RuntimeException $e) {
            return $this->vue('auth/register', [
                'erreurs' => ['piece_recto' => $e->getMessage()],
                'old'     => $request->post,
            ]);
        }

        try {
            $citoyen = $this->auth->inscrireCitoyen(
                nom:                (string) $request->post('nom'),
                email:              (string) $request->post('email'),
                telephone:          (string) $request->post('telephone'),
                motDePasseClair:    (string) $request->post('mot_de_passe'),
                pieceIdentiteRecto: $pieceRecto,
                pieceIdentiteVerso: $pieceVerso,
            );
        } catch (DomainException $e) {
            return $this->vue('auth/register', [
                'erreurs' => ['email' => $e->getMessage()],
                'old'     => $request->post,
            ]);
        }

        // Auto-connexion après inscription
        Session::setUserId((int) $citoyen->getId());
        return $this->redirigerAvecFlash('/', 'success', 'Bienvenue sur CityAlert, ' . $citoyen->getNom() . ' !');
    }

    // ---------- LOGOUT ----------

    public function deconnecter(Request $request): Response
    {
        $this->auth->deconnecter();
        return $this->redirigerAvecFlash('/login', 'info', 'À bientôt !');
    }
}
