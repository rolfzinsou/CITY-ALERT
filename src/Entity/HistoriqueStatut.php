<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Statut;
use App\Trait\Timestampable;

/**
 * Historique d'un changement de statut sur un signalement.
 *
 * Une entrée par transition (Nouveau → En cours, En cours → Résolu, etc.).
 * Créée automatiquement par Signalement::changerStatut().
 */
class HistoriqueStatut
{
    use Timestampable;

    private ?int $id = null;

    public function __construct(
        private Signalement $signalement,
        private Agent $agent,
        private Statut $ancien,
        private Statut $nouveau,
        private ?string $remarque = null,
    ) {
        $this->initTimestamps();
    }

    public function getId(): ?int       { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getSignalement(): Signalement { return $this->signalement; }
    public function getAgent(): Agent             { return $this->agent; }
    public function getAncien(): Statut           { return $this->ancien; }
    public function getNouveau(): Statut          { return $this->nouveau; }
    public function getRemarque(): ?string        { return $this->remarque; }
    public function setRemarque(?string $r): void { $this->remarque = $r; }
}
