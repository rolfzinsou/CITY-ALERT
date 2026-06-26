<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Priorite;
use App\Enum\TypeCategorie;

class Dechets extends Categorie
{
    public function __construct(string $nom = 'Déchets')
    {
        parent::__construct($nom);
    }

    public function getPriorite(): Priorite      { return Priorite::NORMALE; }
    public function getDelaiTraitementJours(): int { return 3; }
    public function getTypeClass(): TypeCategorie  { return TypeCategorie::DECHETS; }
}
