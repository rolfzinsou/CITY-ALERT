<?php

declare(strict_types=1);

namespace App\Trait;

use DateTimeImmutable;

/**
 * Trait Timestampable
 *
 * Ajoute deux dates aux entités (createdAt, updatedAt) ainsi que les
 * helpers pour les manipuler. Exigence POO du sujet (« utiliser au moins
 * un trait, par exemple Timestampable »).
 *
 * Utilisation :
 *   class MaClasse {
 *       use Timestampable;
 *       public function __construct() { $this->initTimestamps(); }
 *   }
 */
trait Timestampable
{
    protected DateTimeImmutable $createdAt;
    protected DateTimeImmutable $updatedAt;

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Marque l'entité comme modifiée maintenant.
     * Appeler dans tout setter / méthode de mutation.
     */
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * À appeler dans le constructeur d'une entité fraîche.
     */
    protected function initTimestamps(): void
    {
        $now             = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    /**
     * Utilisé par les repositories lors de l'hydratation depuis la BDD.
     */
    public function setTimestamps(DateTimeImmutable $createdAt, DateTimeImmutable $updatedAt): void
    {
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
}
