<?php

declare(strict_types=1);

namespace App\Entity;

use App\Trait\Timestampable;

/**
 * Photo attachée à un Signalement.
 *
 * Relation : Signalement 1 ──── n Photo
 * Permet plusieurs clichés par signalement (vue prévisualisation type e-commerce).
 */
class Photo
{
    use Timestampable;

    private ?int $id = null;

    public function __construct(
        private string $chemin,
        private Signalement $signalement,
        private int $ordre = 0,
    ) {
        $this->initTimestamps();
    }

    public function getId(): ?int       { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getChemin(): string { return $this->chemin; }
    public function setChemin(string $c): void { $this->chemin = $c; $this->touch(); }

    public function getSignalement(): Signalement { return $this->signalement; }
    public function setSignalement(Signalement $s): void { $this->signalement = $s; }

    public function getOrdre(): int    { return $this->ordre; }
    public function setOrdre(int $o): void { $this->ordre = $o; $this->touch(); }
}
