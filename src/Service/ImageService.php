<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

/**
 * ImageService — manipulation d'images via l'extension GD.
 *
 * Couvre deux des bonus du sujet : redimensionnement automatique des
 * photos uploadées + génération de miniatures.
 */
final class ImageService
{
    public const MAX_WIDTH      = 1920;
    public const THUMB_WIDTH    = 400;
    public const JPEG_QUALITY   = 85;
    public const WEBP_QUALITY   = 82;

    /**
     * Redimensionne une image en respectant le ratio si elle dépasse $maxWidth.
     * Écrit le résultat sur place.
     */
    public function redimensionner(string $cheminFichier, int $maxWidth = self::MAX_WIDTH): void
    {
        $image = $this->charger($cheminFichier);
        [$src, $srcW, $srcH, $type] = $image;

        if ($srcW <= $maxWidth) {
            imagedestroy($src);
            return;
        }

        $ratio = $maxWidth / $srcW;
        $dstW  = $maxWidth;
        $dstH  = (int) round($srcH * $ratio);

        $dst = imagecreatetruecolor($dstW, $dstH);
        $this->preserverTransparence($dst, $type);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        $this->sauvegarder($dst, $cheminFichier, $type);
        imagedestroy($src);
        imagedestroy($dst);
    }

    /**
     * Crée une miniature (largeur fixe, ratio préservé) à partir d'une image source.
     * Retourne le chemin de la miniature.
     */
    public function genererMiniature(string $cheminSource, string $cheminMiniature, int $largeur = self::THUMB_WIDTH): string
    {
        [$src, $srcW, $srcH, $type] = $this->charger($cheminSource);

        $ratio = $largeur / $srcW;
        $dstW  = $largeur;
        $dstH  = (int) round($srcH * $ratio);

        $dst = imagecreatetruecolor($dstW, $dstH);
        $this->preserverTransparence($dst, $type);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        $this->sauvegarder($dst, $cheminMiniature, $type);
        imagedestroy($src);
        imagedestroy($dst);

        return $cheminMiniature;
    }

    /**
     * Recadre l'image selon des coordonnées normalisées (0..1) renvoyées par
     * un cropper côté client (ex : Cropper.js).
     */
    public function recadrer(string $cheminFichier, float $x, float $y, float $w, float $h): void
    {
        [$src, $srcW, $srcH, $type] = $this->charger($cheminFichier);

        $cropX = (int) round($x * $srcW);
        $cropY = (int) round($y * $srcH);
        $cropW = (int) round($w * $srcW);
        $cropH = (int) round($h * $srcH);

        $dst = imagecreatetruecolor($cropW, $cropH);
        $this->preserverTransparence($dst, $type);
        imagecopy($dst, $src, 0, 0, $cropX, $cropY, $cropW, $cropH);

        $this->sauvegarder($dst, $cheminFichier, $type);
        imagedestroy($src);
        imagedestroy($dst);
    }

    // ---------- Internes ----------

    /** @return array{0:\GdImage, 1:int, 2:int, 3:int} */
    private function charger(string $chemin): array
    {
        if (!is_file($chemin)) {
            throw new RuntimeException("Image introuvable : {$chemin}");
        }
        $info = getimagesize($chemin);
        if ($info === false) {
            throw new RuntimeException("Fichier invalide : {$chemin}");
        }
        $type = $info[2];
        $src  = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($chemin),
            IMAGETYPE_PNG  => imagecreatefrompng($chemin),
            IMAGETYPE_WEBP => imagecreatefromwebp($chemin),
            default        => throw new RuntimeException("Format non supporté : {$type}"),
        };
        return [$src, $info[0], $info[1], $type];
    }

    private function preserverTransparence(\GdImage $dst, int $type): void
    {
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, imagesx($dst), imagesy($dst), $transparent);
        }
    }

    private function sauvegarder(\GdImage $img, string $chemin, int $type): void
    {
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($img, $chemin, self::JPEG_QUALITY),
            IMAGETYPE_PNG  => imagepng($img, $chemin, 8),
            IMAGETYPE_WEBP => imagewebp($img, $chemin, self::WEBP_QUALITY),
        };
    }
}
