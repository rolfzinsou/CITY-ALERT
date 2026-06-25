<?php

declare(strict_types=1);

namespace App\Contract;

/**
 * Contrat pour toute entité pouvant recevoir des notifications.
 *
 * Implémenté par Utilisateur (et donc Citoyen, Agent, Administrateur).
 * Permet d'unifier l'envoi de notifications côté Service (MailService,
 * NotificationService) sans connaître la sous-classe concrète.
 *
 * Exigence POO du sujet : NotifiableInterface est l'une des 2 interfaces imposées.
 */
interface NotifiableInterface
{
    /**
     * Envoie une notification (email + éventuellement push web) à l'entité.
     *
     * @param string $message Texte de la notification
     * @param string $type    Type sémantique : 'info', 'success', 'warning', 'urgent'
     */
    public function notifier(string $message, string $type = 'info'): void;

    /**
     * Adresse email utilisée pour les notifications.
     */
    public function getEmailNotification(): string;

    /**
     * Numéro de téléphone (format international, ex. +221XXXXXXXXX).
     */
    public function getTelephoneNotification(): string;

    /**
     * L'utilisateur a-t-il autorisé les notifications ?
     */
    public function accepteNotifications(): bool;
}
