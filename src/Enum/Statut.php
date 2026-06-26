<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Cycle de vie d'un signalement.
 * Le sujet impose ces 4 statuts. Les transitions valides sont
 * encodées ici pour empêcher les changements d'état illégaux.
 */
enum Statut: string
{
    case NOUVEAU  = 'nouveau';
    case EN_COURS = 'en_cours';
    case RESOLU   = 'resolu';
    case REJETE   = 'rejete';

    public function libelle(): string
    {
        return match ($this) {
            self::NOUVEAU  => 'Nouveau',
            self::EN_COURS => 'En cours',
            self::RESOLU   => 'Résolu',
            self::REJETE   => 'Rejeté',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::NOUVEAU  => '#3B82F6',
            self::EN_COURS => '#F59E0B',
            self::RESOLU   => '#10B981',
            self::REJETE   => '#EF4444',
        };
    }

    /**
     * Statuts vers lesquels on peut transitionner depuis le statut courant.
     * Règles métier :
     *   - Nouveau  → En cours, Rejeté
     *   - En cours → Résolu, Rejeté
     *   - Résolu / Rejeté → final (pas de transition)
     *
     * @return Statut[]
     */
    public function transitionsAutorisees(): array
    {
        return match ($this) {
            self::NOUVEAU  => [self::EN_COURS, self::REJETE],
            self::EN_COURS => [self::RESOLU, self::REJETE],
            self::RESOLU,
            self::REJETE   => [],
        };
    }

    public function peutTransitionnerVers(self $cible): bool
    {
        return in_array($cible, $this->transitionsAutorisees(), true);
    }

    public function estFinal(): bool
    {
        return $this->transitionsAutorisees() === [];
    }
}
