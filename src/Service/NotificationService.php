<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Agent;
use App\Entity\HistoriqueStatut;
use App\Entity\Signalement;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;

/**
 * NotificationService — orchestre les notifications.
 *
 * Le sujet impose une notification email au changement de statut.
 * Ce service centralise la décision (faut-il notifier ?) et délègue
 * le transport au MailService.
 *
 * Plus tard : ajout possible de Web Push (navigateur), SMS (Twilio), etc.
 * sans toucher au reste du code.
 */
final class NotificationService
{
    public function __construct(
        private MailService $mailService = new MailService(),
    ) {}

    /**
     * Notifie l'auteur du signalement après un changement de statut.
     */
    public function notifierChangementStatut(
        Signalement $signalement,
        HistoriqueStatut $historique,
    ): void {
        $auteur = $signalement->getAuteur();

        if (!$auteur->accepteNotifications()) {
            return;   // L'utilisateur a désactivé les notifications.
        }

        $this->mailService->notifierChangementStatut(
            destinataire:     $auteur->getEmailNotification(),
            nomDestinataire:  $this->extraireNom($auteur),
            titreSignalement: $signalement->getTitre(),
            ancienStatut:     $historique->getAncien()->libelle(),
            nouveauStatut:    $historique->getNouveau()->libelle(),
            remarque:         $historique->getRemarque(),
        );

        // Délègue aussi à l'entité (qui peut avoir sa propre logique selon le rôle)
        $auteur->notifier(
            sprintf(
                'Votre signalement « %s » est passé en : %s',
                $signalement->getTitre(),
                $historique->getNouveau()->libelle()
            ),
            'info'
        );
    }

    /**
     * Notifie les agents de la catégorie concernée quand un signalement est créé.
     */
    public function notifierNouveauSignalement(
        Signalement $signalement,
        UtilisateurRepository $userRepo,
    ): void {
        $categorieId = $signalement->getCategorie()->getId();
        if ($categorieId === null) {
            return;
        }

        $agents = $userRepo->findAgentsParCategorie($categorieId);
        foreach ($agents as $agent) {
            if (!$agent instanceof Agent || !$agent->accepteNotifications()) {
                continue;
            }
            $this->mailService->notifierNouveauSignalement(
                destinataire:    $agent->getEmailNotification(),
                nomAgent:        $this->extraireNom($agent),
                titreSignalement: $signalement->getTitre(),
                adresse:         $signalement->getAdresse(),
                categorie:       $signalement->getCategorie()->getNom(),
            );
        }
    }

    private function extraireNom(Utilisateur $u): string
    {
        // Récupère le prénom (premier mot du nom complet)
        $parts = explode(' ', $u->getNom(), 2);
        return $parts[0] ?? 'Utilisateur';
    }
}
