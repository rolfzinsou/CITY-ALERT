<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Statut;
use App\Trait\Timestampable;
use DomainException;

/**
 * Signalement — entité centrale.
 *
 * Règles métier encodées ici (et pas dans les contrôleurs) :
 *  - Le statut initial est NOUVEAU
 *  - L'auteur peut modifier/supprimer SI ET SEULEMENT SI statut === NOUVEAU (cf. sujet)
 *  - Le changement de statut suit les transitions définies dans Statut::transitionsAutorisees()
 *  - Le changement de statut produit automatiquement une entrée HistoriqueStatut
 *  - Le soutien (« like ») est compté ici ; la relation N-N citoyen↔signalement est gérée
 *    en BDD via la table d'association `soutiens`
 */
class Signalement
{
    use Timestampable;

    private ?int $id = null;

    /** @var Photo[] */
    private array $photos = [];

    /** @var Commentaire[] */
    private array $commentaires = [];

    private int $nombreSoutiens = 0;

    public function __construct(
        private string $titre,
        private string $description,
        private string $adresse,
        private Utilisateur $auteur,
        private Categorie $categorie,
        private Statut $statut = Statut::NOUVEAU,
        private ?float $latitude = null,
        private ?float $longitude = null,
    ) {
        $this->initTimestamps();
    }

    // ---------- Règles métier ----------

    /**
     * Un signalement n'est modifiable / supprimable par son auteur QUE
     * tant que son statut est NOUVEAU (exigence du sujet).
     */
    public function peutEtreModifiePar(Utilisateur $utilisateur): bool
    {
        return $this->auteur->getId() !== null
            && $this->auteur->getId() === $utilisateur->getId()
            && $this->statut === Statut::NOUVEAU;
    }

    public function peutEtreSupprimePar(Utilisateur $utilisateur): bool
    {
        return $this->peutEtreModifiePar($utilisateur);
    }

    /**
     * Change le statut et crée l'entrée d'historique correspondante.
     * Refuse les transitions invalides.
     *
     * @throws DomainException si la transition est interdite
     */
    public function changerStatut(Statut $nouveau, Agent $agent, ?string $remarque = null): HistoriqueStatut
    {
        if (!$this->statut->peutTransitionnerVers($nouveau)) {
            throw new DomainException(sprintf(
                'Transition interdite : %s → %s',
                $this->statut->libelle(),
                $nouveau->libelle()
            ));
        }

        if (!$agent->peutTraiter($this)) {
            throw new DomainException("L'agent n'est pas habilité à traiter cette catégorie.");
        }

        $ancien        = $this->statut;
        $this->statut  = $nouveau;
        $this->touch();

        return new HistoriqueStatut(
            signalement: $this,
            agent:       $agent,
            ancien:      $ancien,
            nouveau:     $nouveau,
            remarque:    $remarque,
        );
    }

    public function ajouterPhoto(Photo $photo): void
    {
        $this->photos[] = $photo;
    }

    public function ajouterCommentaire(Commentaire $c): void
    {
        $this->commentaires[] = $c;
    }

    public function incrementerSoutiens(): void
    {
        $this->nombreSoutiens++;
    }

    public function decrementerSoutiens(): void
    {
        if ($this->nombreSoutiens > 0) {
            $this->nombreSoutiens--;
        }
    }

    // ---------- Getters / setters ----------
    public function getId(): ?int       { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getTitre(): string  { return $this->titre; }
    public function setTitre(string $t): void { $this->titre = $t; $this->touch(); }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $d): void { $this->description = $d; $this->touch(); }

    public function getAdresse(): string { return $this->adresse; }
    public function setAdresse(string $a): void { $this->adresse = $a; $this->touch(); }

    public function getStatut(): Statut { return $this->statut; }
    /** @internal réservé aux repositories pour l'hydratation */
    public function setStatut(Statut $s): void { $this->statut = $s; }

    public function getAuteur(): Utilisateur    { return $this->auteur; }
    public function getCategorie(): Categorie   { return $this->categorie; }
    public function setCategorie(Categorie $c): void { $this->categorie = $c; $this->touch(); }

    public function getLatitude(): ?float       { return $this->latitude; }
    public function setLatitude(?float $l): void { $this->latitude = $l; }
    public function getLongitude(): ?float      { return $this->longitude; }
    public function setLongitude(?float $l): void { $this->longitude = $l; }

    /** @return Photo[] */
    public function getPhotos(): array { return $this->photos; }
    public function setPhotos(array $photos): void { $this->photos = $photos; }

    /** @return Commentaire[] */
    public function getCommentaires(): array { return $this->commentaires; }
    public function setCommentaires(array $cs): void { $this->commentaires = $cs; }

    public function getNombreSoutiens(): int { return $this->nombreSoutiens; }
    public function setNombreSoutiens(int $n): void { $this->nombreSoutiens = $n; }
}
