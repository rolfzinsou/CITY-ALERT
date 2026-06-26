<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Priorite;
use App\Enum\TypeCategorie;

class Eclairage extends Categorie
{
    public function __construct(string $nom = 'Éclairage')
    {
        parent::__construct($nom);
    }

    public function getPriorite(): Priorite      { return Priorite::NORMALE; }
    public function getDelaiTraitementJours(): int { return 5; }
    public function getTypeClass(): TypeCategorie  { return TypeCategorie::ECLAIRAGE; }
}
