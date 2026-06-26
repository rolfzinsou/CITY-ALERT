<?php

declare(strict_types=1);

namespace App\Entity;

use App\Contract\NotifiableInterface;
use App\Enum\Role;
use App\Trait\Timestampable;

/**
 * Classe abstraite Utilisateur (exigence POO du sujet).
 *
 * Héritage : Citoyen, Agent, Administrateur.
 * Implémente NotifiableInterface, utilise Timestampable.
 *
 * Stratégie BDD : Single Table Inheritance (table unique `utilisateurs`
 * avec colonne discriminante `role`).
 */
abstract class Utilisateur implements NotifiableInterface
{
    use Timestampable;

    protected ?int $id = null;
    protected bool $notificationsActives = true;

    public function __construct(
        protected string $nom,
        protected string $email,
        protected string $motDePasse,
        protected string $telephone,
    ) {
        $this->initTimestamps();
    }

    // ---------- Méthodes abstraites ----------
    /** Chaque sous-classe expose son rôle. */
    abstract public function getRole(): Role;

    /** Implémentation propre à chaque rôle (canaux, formatage). */
    abstract public function notifier(string $message, string $type = 'info'): void;

    // ---------- NotifiableInterface ----------
    public function getEmailNotification(): string
    {
        return $this->email;
    }

    public function getTelephoneNotification(): string
    {
        return $this->telephone;
    }

    public function accepteNotifications(): bool
    {
        return $this->notificationsActives;
    }

    public function activerNotifications(bool $actif): void
    {
        $this->notificationsActives = $actif;
        $this->touch();
    }

    // ---------- Authentification ----------
    public function verifierMotDePasse(string $clair): bool
    {
        return password_verify($clair, $this->motDePasse);
    }

    public function changerMotDePasse(string $clair): void
    {
        $this->motDePasse = password_hash($clair, PASSWORD_DEFAULT);
        $this->touch();
    }

    // ---------- Getters / setters ----------
    public function getId(): ?int        { return $this->id; }
    public function setId(int $id): void  { $this->id = $id; }

    public function getNom(): string      { return $this->nom; }
    public function setNom(string $n): void { $this->nom = $n; $this->touch(); }

    public function getEmail(): string    { return $this->email; }
    public function setEmail(string $e): void { $this->email = $e; $this->touch(); }

    public function getMotDePasseHash(): string { return $this->motDePasse; }
    public function setMotDePasseHash(string $h): void { $this->motDePasse = $h; }

    public function getTelephone(): string { return $this->telephone; }
    public function setTelephone(string $t): void { $this->telephone = $t; $this->touch(); }
}
