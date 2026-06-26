<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Discriminateur des sous-classes de Categorie.
 * Sert au mapping ligne SQL → instance PHP (Voirie, Eclairage, Dechets, EauAssainissement).
 */
enum TypeCategorie: string
{
    case VOIRIE             = 'voirie';
    case ECLAIRAGE          = 'eclairage';
    case DECHETS            = 'dechets';
    case EAU_ASSAINISSEMENT = 'eau_assainissement';

    public function libelle(): string
    {
        return match ($this) {
            self::VOIRIE             => 'Voirie',
            self::ECLAIRAGE          => 'Éclairage',
            self::DECHETS            => 'Déchets',
            self::EAU_ASSAINISSEMENT => 'Eau et assainissement',
        };
    }
}
