<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Role;

/**
 * Agent municipal.
 *
 * Règles métier :
 *  - Compte créé UNIQUEMENT par un Administrateur
 *  - Rattaché à exactement 1 Categorie qu'il est habilité à traiter
 *  - Peut changer le statut d'un signalement de sa catégorie et ajouter des
 *    remarques de traitement
 */
class Agent extends Utilisateur
{
    private string $service;
    private ?Categorie $categorie = null;

    public function __construct(
        string $nom,
        string $email,
        string $motDePasse,
        string $telephone,
        string $service,
        ?Categorie $categorie = null,
    ) {
        parent::__construct($nom, $email, $motDePasse, $telephone);
        $this->service   = $service;
        $this->categorie = $categorie;
    }

    public function getRole(): Role
    {
        return Role::AGENT;
    }

    public function notifier(string $message, string $type = 'info'): void
    {
        if (!$this->accepteNotifications()) {
            return;
        }
        // Idem : le service de notification gère l'envoi réel.
    }

    /**
     * Vrai si l'agent est habilité à traiter ce signalement (= même catégorie).
     */
    public function peutTraiter(Signalement $signalement): bool
    {
        if ($this->categorie === null) {
            return false;
        }
        $sigCat = $signalement->getCategorie();
        return $sigCat->getId() !== null
            && $sigCat->getId() === $this->categorie->getId();
    }

    // ---------- Getters / setters spécifiques ----------
    public function getService(): string
    {
        return $this->service;
    }

    public function setService(string $s): void
    {
        $this->service = $s;
        $this->touch();
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $c): void
    {
        $this->categorie = $c;
        $this->touch();
    }
}
