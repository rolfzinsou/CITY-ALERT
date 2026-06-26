<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

/**
 * UploadService — validation + déplacement + post-traitement des fichiers uploadés.
 *
 * Sécurité :
 *  - Vérifie type MIME réel (pas seulement l'extension)
 *  - Limite la taille (UPLOAD_MAX_SIZE)
 *  - Génère un nom de fichier aléatoire (jamais le nom client)
 *
 * Intègre ImageService pour redimensionner automatiquement.
 */
final class UploadService
{
    public function __construct(
        private ImageService $imageService = new ImageService(),
    ) {}

    /**
     * Traite un upload simple (un seul fichier).
     *
     * @param array<string, mixed> $file        Entrée $_FILES['xxx']
     * @param string               $sousDossier Sous-dossier de UPLOAD_PATH (ex. 'signalements')
     * @return string Chemin relatif (à stocker en BDD), ex. '/uploads/signalements/abc.jpg'
     */
    public function gererUploadPhoto(array $file, string $sousDossier = 'signalements'): string
    {
        $this->valider($file);

        $destinationDir = UPLOAD_PATH . '/' . trim($sousDossier, '/');
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        $extension = $this->extensionDepuisMimeType($file['type']);
        $nomFichier = bin2hex(random_bytes(8)) . '_' . time() . '.' . $extension;
        $cheminAbsolu = $destinationDir . '/' . $nomFichier;

        if (!move_uploaded_file($file['tmp_name'], $cheminAbsolu)) {
            throw new RuntimeException("Impossible de déplacer le fichier uploadé.");
        }

        // Redimensionnement automatique (bonus du sujet)
        $this->imageService->redimensionner($cheminAbsolu);

        return '/uploads/' . trim($sousDossier, '/') . '/' . $nomFichier;
    }

    /**
     * Traite un upload multiple (champ `<input name="photos[]" multiple>`).
     *
     * @param array<string, mixed> $files Entrée $_FILES['photos'] (forme multi)
     * @return string[] Chemins relatifs
     */
    public function gererUploadPhotosMultiple(array $files, string $sousDossier = 'signalements'): array
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $chemins = [];
        $nb      = count($files['name']);

        for ($i = 0; $i < $nb; $i++) {
            // Réorganiser l'entrée pour la passer à gererUploadPhoto()
            $unFichier = [
                'name'     => $files['name'][$i]     ?? '',
                'type'     => $files['type'][$i]     ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error'    => $files['error'][$i]    ?? UPLOAD_ERR_NO_FILE,
                'size'     => $files['size'][$i]     ?? 0,
            ];
            if ($unFichier['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $chemins[] = $this->gererUploadPhoto($unFichier, $sousDossier);
        }

        return $chemins;
    }

    // ---------- Validation ----------

    /** @param array<string, mixed> $file */
    private function valider(array $file): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->messageErreurUpload((int) ($file['error'] ?? 0)));
        }
        if (!is_uploaded_file((string) $file['tmp_name'])) {
            throw new RuntimeException("Fichier non uploadé par HTTP.");
        }
        if ((int) ($file['size'] ?? 0) > UPLOAD_MAX_SIZE) {
            throw new RuntimeException(sprintf(
                "Fichier trop volumineux (max %d Mo).",
                UPLOAD_MAX_SIZE / 1024 / 1024
            ));
        }

        // Vrai type MIME, pas celui fourni par le client (qui peut mentir)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file((string) $file['tmp_name']);
        if (!in_array($mime, UPLOAD_ALLOWED_TYPES, true)) {
            throw new RuntimeException("Format non autorisé : {$mime}");
        }
    }

    private function extensionDepuisMimeType(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'bin',
        };
    }

    private function messageErreurUpload(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Fichier trop volumineux.",
            UPLOAD_ERR_PARTIAL                        => "Upload incomplet.",
            UPLOAD_ERR_NO_FILE                        => "Aucun fichier reçu.",
            UPLOAD_ERR_NO_TMP_DIR                     => "Dossier temporaire manquant côté serveur.",
            UPLOAD_ERR_CANT_WRITE                     => "Écriture impossible.",
            UPLOAD_ERR_EXTENSION                      => "Extension PHP a bloqué l'upload.",
            default                                   => "Erreur d'upload inconnue.",
        };
    }
}
