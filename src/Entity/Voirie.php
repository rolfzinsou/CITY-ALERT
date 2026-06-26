<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Priorite;
use App\Enum\TypeCategorie;

class Voirie extends Categorie
{
    public function __construct(string $nom = 'Voirie')
    {
        parent::__construct($nom);
    }

    public function getPriorite(): Priorite      { return Priorite::HAUTE; }
    public function getDelaiTraitementJours(): int { return 7; }
    public function getTypeClass(): TypeCategorie  { return TypeCategorie::VOIRIE; }
}
