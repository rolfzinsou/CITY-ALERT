<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Session;
use App\Entity\Citoyen;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use DomainException;
use PDO;
use App\Core\Database;

/**
 * AuthService — règles d'authentification.
 *
 *  - Inscription : crée toujours un Citoyen (jamais un Agent ni un Admin)
 *  - Connexion : identifiant = email OU téléphone (+221...)
 *  - "Se souvenir de moi" : cookie persistant (selecteur + validateur hashé)
 *    Référence : https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
 */
final class AuthService
{
    private const REMEMBER_COOKIE = 'cityalert_remember';

    public function __construct(
        private UtilisateurRepository $userRepo,
    ) {}

    // =========================================================
    //  Inscription
    // =========================================================

    /**
     * Inscrit un nouveau Citoyen. Ne peut PAS créer un Agent ni un Admin
     * (l'inscription publique est réservée aux citoyens — règle métier).
     *
     * @throws DomainException si l'email ou le téléphone existe déjà.
     */
    public function inscrireCitoyen(
        string $nom,
        string $email,
        string $telephone,
        string $motDePasseClair,
        ?string $pieceIdentiteRecto = null,
        ?string $pieceIdentiteVerso = null,
    ): Citoyen {
        if ($this->userRepo->findByEmail($email) !== null) {
            throw new DomainException('Cet email est déjà utilisé.');
        }
        if ($this->userRepo->findByTelephone($telephone) !== null) {
            throw new DomainException('Ce numéro de téléphone est déjà utilisé.');
        }

        $citoyen = new Citoyen(
            nom:                $nom,
            email:              $email,
            motDePasse:         password_hash($motDePasseClair, PASSWORD_DEFAULT),
            telephone:          $telephone,
            pieceIdentiteRecto: $pieceIdentiteRecto,
            pieceIdentiteVerso: $pieceIdentiteVerso,
        );
        $this->userRepo->save($citoyen);
        return $citoyen;
    }

    // =========================================================
    //  Connexion
    // =========================================================

    /**
     * Connexion par email OU téléphone (selon ce que l'utilisateur saisit).
     *
     * @return Utilisateur|null Null si identifiants invalides.
     */
    public function connecter(string $identifiant, string $motDePasseClair, bool $sesouvenir = false): ?Utilisateur
    {
        $user = $this->userRepo->findByEmailOuTelephone(trim($identifiant));
        if ($user === null || !$user->verifierMotDePasse($motDePasseClair)) {
            return null;
        }

        Session::setUserId((int) $user->getId());

        if ($sesouvenir) {
            $this->creerTokenRememberMe($user);
        }
        return $user;
    }

    public function deconnecter(): void
    {
        $userId = Session::getUserId();
        if ($userId !== null) {
            $this->effacerTokensRememberMe($userId);
        }
        $this->effacerCookieRemember();
        Session::clear();
    }

    public function utilisateurConnecte(): ?Utilisateur
    {
        $id = Session::getUserId();
        if ($id === null) {
            // Tentative de reconnexion via cookie "Se souvenir de moi"
            return $this->essayerCookieRemember();
        }
        return $this->userRepo->find($id);
    }

    // =========================================================
    //  Cookies "Se souvenir de moi" (sécurisé)
    // =========================================================

    private function creerTokenRememberMe(Utilisateur $user): void
    {
        $selecteur  = bin2hex(random_bytes(16));   // public, identifie le token
        $validateur = bin2hex(random_bytes(32));   // secret, vérifié côté serveur
        $hash       = hash('sha256', $validateur);

        $expireAt = (new \DateTimeImmutable())->modify('+' . REMEMBER_ME_LIFETIME . ' seconds');

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'INSERT INTO remember_tokens (utilisateur_id, selecteur, validateur_hash, expire_at)
             VALUES (:u, :s, :v, :e)'
        );
        $stmt->execute([
            'u' => $user->getId(),
            's' => $selecteur,
            'v' => $hash,
            'e' => $expireAt->format('Y-m-d H:i:s'),
        ]);

        // Le cookie contient "selecteur:validateur" — seul le hash est en BDD
        setcookie(
            self::REMEMBER_COOKIE,
            "$selecteur:$validateur",
            [
                'expires'  => time() + REMEMBER_ME_LIFETIME,
                'path'     => '/',
                'secure'   => false,           // true en HTTPS prod
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    private function essayerCookieRemember(): ?Utilisateur
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? null;
        if ($cookie === null || !str_contains($cookie, ':')) {
            return null;
        }
        [$selecteur, $validateur] = explode(':', $cookie, 2);

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT * FROM remember_tokens WHERE selecteur = :s AND expire_at > NOW() LIMIT 1'
        );
        $stmt->execute(['s' => $selecteur]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        if (!hash_equals((string) $row['validateur_hash'], hash('sha256', $validateur))) {
            return null;
        }

        // Token valide → connexion silencieuse
        $user = $this->userRepo->find((int) $row['utilisateur_id']);
        if ($user !== null) {
            Session::setUserId((int) $user->getId());
        }
        return $user;
    }

    private function effacerTokensRememberMe(int $userId): void
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE utilisateur_id = :u');
        $stmt->execute(['u' => $userId]);
    }

    private function effacerCookieRemember(): void
    {
        if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            setcookie(self::REMEMBER_COOKIE, '', time() - 3600, '/');
            unset($_COOKIE[self::REMEMBER_COOKIE]);
        }
    }
}
