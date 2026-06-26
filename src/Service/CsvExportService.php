<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;

/**
 * CsvExportService — export CSV des signalements (bonus du sujet).
 *
 * Supporte l'export complet OU filtré par catégorie (demande du user).
 */
final class CsvExportService
{
    public function __construct(
        private SignalementRepository $signRepo,
    ) {}

    /**
     * Exporte vers un buffer CSV.
     *
     * @return string Le CSV (peut être renvoyé directement avec Content-Type: text/csv)
     */
    public function exporter(?int $categorieId = null): string
    {
        $signalements = $this->signRepo->rechercher(
            $categorieId !== null ? ['categorieId' => $categorieId] : []
        );

        $fh = fopen('php://temp', 'w+');
        if ($fh === false) {
            return '';
        }

        // BOM UTF-8 (pour qu'Excel ouvre correctement les accents)
        fwrite($fh, "\xEF\xBB\xBF");

        // En-têtes
        fputcsv($fh, [
            'ID',
            'Titre',
            'Description',
            'Adresse',
            'Latitude',
            'Longitude',
            'Catégorie',
            'Statut',
            'Auteur',
            'Email auteur',
            'Téléphone auteur',
            'Date de création',
            'Nombre de soutiens',
        ], ';');

        foreach ($signalements as $s) {
            fputcsv($fh, $this->ligne($s), ';');
        }

        rewind($fh);
        $csv = (string) stream_get_contents($fh);
        fclose($fh);
        return $csv;
    }

    /** @return string[] */
    private function ligne(Signalement $s): array
    {
        return [
            (string) $s->getId(),
            $s->getTitre(),
            $s->getDescription(),
            $s->getAdresse(),
            (string) ($s->getLatitude()  ?? ''),
            (string) ($s->getLongitude() ?? ''),
            $s->getCategorie()->getNom(),
            $s->getStatut()->libelle(),
            $s->getAuteur()->getNom(),
            $s->getAuteur()->getEmailNotification(),
            $s->getAuteur()->getTelephoneNotification(),
            $s->getCreatedAt()->format('Y-m-d H:i'),
            (string) $s->getNombreSoutiens(),
        ];
    }

    /**
     * Helper : nom de fichier suggéré pour le download.
     */
    public function nomFichier(?int $categorieId = null): string
    {
        $suffixe = $categorieId !== null ? "cat-{$categorieId}" : 'tous';
        return sprintf('cityalert-signalements-%s-%s.csv', $suffixe, date('Y-m-d'));
    }
}
