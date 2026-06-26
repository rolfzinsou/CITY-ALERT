<?php

declare(strict_types=1);

namespace App\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

final class MailService
{
    public function envoyer(string $destinataire, string $sujet, string $corpsHtml): bool
    {
        if (APP_ENV === 'development') {
            $log = sprintf(
                "[%s] To: %s | Subject: %s\n%s\n\n",
                date('Y-m-d H:i:s'),
                $destinataire,
                $sujet,
                strip_tags($corpsHtml)
            );
            error_log($log, 3, ROOT_PATH . '/logs/mail.log');
            return true;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($destinataire);

            $mail->isHTML(true);
            $mail->Subject = $sujet;
            $mail->Body    = $corpsHtml;
            $mail->AltBody = strip_tags($corpsHtml);

            $mail->send();
            return true;
        } catch (MailException) {
            error_log('[MailService] Erreur envoi : ' . $mail->ErrorInfo, 3, ROOT_PATH . '/logs/mail.log');
            return false;
        }
    }

    public function notifierNouveauSignalement(
        string $destinataire,
        string $nomAgent,
        string $titreSignalement,
        string $adresse,
        string $categorie,
    ): bool {
        $sujet = "Nouveau signalement dans votre domaine : « {$titreSignalement} »";
        $html  = <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px;background:#f8f9fa">
  <h2 style="color:#10B981">CityAlert</h2>
  <p>Bonjour {$nomAgent},</p>
  <p>Un nouveau signalement vient d'être déposé dans votre domaine <strong>{$categorie}</strong> :</p>
  <table style="width:100%;border-collapse:collapse;margin:16px 0">
    <tr><td style="padding:8px;color:#555">Titre</td><td style="padding:8px;font-weight:600">{$titreSignalement}</td></tr>
    <tr style="background:#fff"><td style="padding:8px;color:#555">Adresse</td><td style="padding:8px">{$adresse}</td></tr>
    <tr><td style="padding:8px;color:#555">Catégorie</td><td style="padding:8px">{$categorie}</td></tr>
  </table>
  <p style="margin-top:24px">
    <a href="{APP_URL}" style="background:#10B981;color:white;padding:10px 20px;text-decoration:none;border-radius:8px">
      Voir sur CityAlert
    </a>
  </p>
  <hr style="border:none;border-top:1px solid #ddd;margin:24px 0">
  <p style="font-size:12px;color:#888">Cet email est envoyé automatiquement par CityAlert.</p>
</div>
HTML;
        $html = str_replace('{APP_URL}', APP_URL, $html);
        return $this->envoyer($destinataire, $sujet, $html);
    }

    public function notifierChangementStatut(
        string $destinataire,
        string $nomDestinataire,
        string $titreSignalement,
        string $ancienStatut,
        string $nouveauStatut,
        ?string $remarque = null,
    ): bool {
        $sujet = "Mise à jour de votre signalement : « {$titreSignalement} »";
        $html = <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;padding:24px;background:#f8f9fa">
  <h2 style="color:#10B981">CityAlert</h2>
  <p>Bonjour {$nomDestinataire},</p>
  <p>Votre signalement <strong>{$titreSignalement}</strong> a changé de statut :</p>
  <p style="font-size:18px"><strong>{$ancienStatut}</strong> &rarr; <strong>{$nouveauStatut}</strong></p>
HTML;

        if ($remarque !== null && $remarque !== '') {
            $remarqueEsc = htmlspecialchars($remarque);
            $html .= "<p><em>Remarque de l'agent :</em> {$remarqueEsc}</p>";
        }

        $html .= <<<HTML
  <p style="margin-top:24px">
    <a href="{APP_URL}" style="background:#10B981;color:white;padding:10px 20px;text-decoration:none;border-radius:8px">
      Voir le signalement
    </a>
  </p>
  <hr style="border:none;border-top:1px solid #ddd;margin:24px 0">
  <p style="font-size:12px;color:#888">Cet email est envoyé automatiquement par CityAlert.</p>
</div>
HTML;

        $html = str_replace('{APP_URL}', APP_URL, $html);
        return $this->envoyer($destinataire, $sujet, $html);
    }
}
