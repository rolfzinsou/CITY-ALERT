<?php

declare(strict_types=1);

namespace App\Entity;

use App\Trait\Timestampable;

/**
 * Commentaire — récursif (un commentaire peut être une réponse à un autre).
 *
 * Le champ `parent` permet l'arborescence type Facebook.
 * Les likes sont comptés ici ; la table d'association `likes_commentaires`
 * stocke qui a liké quoi (gérée par le repository).
 */
class Commentaire
{
    use Timestampable;

    private ?int $id = null;
    private int $nombreLikes = 0;

    /** @var Commentaire[] */
    private array $reponses = [];

    public function __construct(
        private string $contenu,
        private Utilisateur $auteur,
        private Signalement $signalement,
        private ?Commentaire $parent = null,
    ) {
        $this->initTimestamps();
    }

    public function estReponse(): bool
    {
        return $this->parent !== null;
    }

    public function ajouterReponse(Commentaire $r): void
    {
        $this->reponses[] = $r;
    }

    public function incrementerLikes(): void
    {
        $this->nombreLikes++;
    }

    public function decrementerLikes(): void
    {
        if ($this->nombreLikes > 0) {
            $this->nombreLikes--;
        }
    }

    // ---------- Getters / setters ----------
    public function getId(): ?int       { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getContenu(): string { return $this->contenu; }
    public function setContenu(string $c): void { $this->contenu = $c; $this->touch(); }

    public function getAuteur(): Utilisateur     { return $this->auteur; }
    public function getSignalement(): Signalement { return $this->signalement; }

    public function getParent(): ?Commentaire    { return $this->parent; }
    public function setParent(?Commentaire $p): void { $this->parent = $p; }

    /** @return Commentaire[] */
    public function getReponses(): array { return $this->reponses; }
    public function setReponses(array $rs): void { $this->reponses = $rs; }

    public function getNombreLikes(): int { return $this->nombreLikes; }
    public function setNombreLikes(int $n): void { $this->nombreLikes = $n; }
}
