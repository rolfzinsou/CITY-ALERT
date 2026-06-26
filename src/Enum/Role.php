<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Rôles des utilisateurs de la plateforme.
 * - CITOYEN        : tout inscrit par défaut
 * - AGENT          : créé uniquement par un Administrateur, rattaché à 1 catégorie
 * - ADMINISTRATEUR : créé en base, gère tout
 */
enum Role: string
{
    case CITOYEN        = 'citoyen';
    case AGENT          = 'agent';
    case ADMINISTRATEUR = 'administrateur';

    public function libelle(): string
    {
        return match ($this) {
            self::CITOYEN        => 'Citoyen',
            self::AGENT          => 'Agent municipal',
            self::ADMINISTRATEUR => 'Administrateur',
        };
    }
}
