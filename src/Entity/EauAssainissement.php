<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Priorite;
use App\Enum\TypeCategorie;

class EauAssainissement extends Categorie
{
    public function __construct(string $nom = 'Eau et assainissement')
    {
        parent::__construct($nom);
    }

    public function getPriorite(): Priorite      { return Priorite::URGENTE; }
    public function getDelaiTraitementJours(): int { return 2; }
    public function getTypeClass(): TypeCategorie  { return TypeCategorie::EAU_ASSAINISSEMENT; }
}
