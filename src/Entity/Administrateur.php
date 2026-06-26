<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Role;

/**
 * Administrateur.
 *
 * Responsabilités :
 *  - Créer et gérer les comptes agents (avec affectation à une catégorie)
 *  - Vérifier les pièces d'identité des citoyens
 *  - Accès tableau de bord global, statistiques, export
 */
class Administrateur extends Utilisateur
{
    private int $niveauAcces;

    public function __construct(
        string $nom,
        string $email,
        string $motDePasse,
        string $telephone,
        int $niveauAcces = 1,
    ) {
        parent::__construct($nom, $email, $motDePasse, $telephone);
        $this->niveauAcces = $niveauAcces;
    }

    public function getRole(): Role
    {
        return Role::ADMINISTRATEUR;
    }

    public function notifier(string $message, string $type = 'info'): void
    {
        if (!$this->accepteNotifications()) {
            return;
        }
        // Délégation au service de notification.
    }

    public function getNiveauAcces(): int
    {
        return $this->niveauAcces;
    }

    public function setNiveauAcces(int $n): void
    {
        $this->niveauAcces = $n;
        $this->touch();
    }
}
