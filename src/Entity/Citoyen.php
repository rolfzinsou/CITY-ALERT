<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Role;
use DateTimeImmutable;

/**
 * Citoyen — rôle par défaut à l'inscription publique.
 *
 * Spécificités :
 *  - Date d'inscription
 *  - Photos de pièce d'identité (recto / verso) pour vérification par l'admin
 *  - Indicateur `verifie` géré par l'admin (n'empêche pas l'usage)
 */
class Citoyen extends Utilisateur
{
    private DateTimeImmutable $dateInscription;
    private ?string $pieceIdentiteRecto = null;
    private ?string $pieceIdentiteVerso = null;
    private bool $verifie = false;

    public function __construct(
        string $nom,
        string $email,
        string $motDePasse,
        string $telephone,
        ?string $pieceIdentiteRecto = null,
        ?string $pieceIdentiteVerso = null,
    ) {
        parent::__construct($nom, $email, $motDePasse, $telephone);
        $this->dateInscription    = new DateTimeImmutable();
        $this->pieceIdentiteRecto = $pieceIdentiteRecto;
        $this->pieceIdentiteVerso = $pieceIdentiteVerso;
    }

    public function getRole(): Role
    {
        return Role::CITOYEN;
    }

    public function notifier(string $message, string $type = 'info'): void
    {
        if (!$this->accepteNotifications()) {
            return;
        }
        // Délégation à un service injecté par le contrôleur (MailService, NotificationService).
        // L'implémentation concrète est faite côté service ; ici l'entité expose juste l'intention.
    }

    // ---------- Spécifiques citoyen ----------
    public function getDateInscription(): DateTimeImmutable
    {
        return $this->dateInscription;
    }

    public function setDateInscription(DateTimeImmutable $d): void
    {
        $this->dateInscription = $d;
    }

    public function getPieceIdentiteRecto(): ?string
    {
        return $this->pieceIdentiteRecto;
    }

    public function setPieceIdentiteRecto(?string $chemin): void
    {
        $this->pieceIdentiteRecto = $chemin;
        $this->touch();
    }

    public function getPieceIdentiteVerso(): ?string
    {
        return $this->pieceIdentiteVerso;
    }

    public function setPieceIdentiteVerso(?string $chemin): void
    {
        $this->pieceIdentiteVerso = $chemin;
        $this->touch();
    }

    public function aFourniPiecesIdentite(): bool
    {
        return $this->pieceIdentiteRecto !== null && $this->pieceIdentiteVerso !== null;
    }

    public function estVerifie(): bool
    {
        return $this->verifie;
    }

    public function setVerifie(bool $verifie): void
    {
        $this->verifie = $verifie;
        $this->touch();
    }
}
