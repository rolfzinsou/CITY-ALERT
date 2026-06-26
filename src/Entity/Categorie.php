<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Priorite;
use App\Enum\TypeCategorie;

/**
 * Classe abstraite Categorie.
 *
 * Le sujet impose une hiérarchie polymorphique : la priorité et le délai
 * varient selon le type (Voirie, Eclairage, Dechets, EauAssainissement).
 * Plutôt que de stocker ces règles en BDD, on les encode dans les sous-classes.
 *
 * Stratégie BDD : table `categories` unique avec colonne `type_class`
 * comme discriminateur ; le repository instancie la bonne sous-classe.
 */
abstract class Categorie
{
    protected ?int $id = null;

    public function __construct(
        protected string $nom,
    ) {}

    // ---------- Méthodes abstraites (polymorphisme) ----------
    abstract public function getPriorite(): Priorite;
    abstract public function getDelaiTraitementJours(): int;
    abstract public function getTypeClass(): TypeCategorie;

    // ---------- Getters / setters ----------
    public function getId(): ?int       { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getNom(): string    { return $this->nom; }
    public function setNom(string $n): void { $this->nom = $n; }
}
