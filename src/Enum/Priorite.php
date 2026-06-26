<?php

declare(strict_types=1);

namespace App\Enum;

enum Priorite: int
{
    case BASSE   = 1;
    case NORMALE = 2;
    case HAUTE   = 3;
    case URGENTE = 4;

    public function libelle(): string
    {
        return match ($this) {
            self::BASSE   => 'Basse',
            self::NORMALE => 'Normale',
            self::HAUTE   => 'Haute',
            self::URGENTE => 'Urgente',
        };
    }
}
