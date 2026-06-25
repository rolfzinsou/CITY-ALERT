<?php
/**
 * src/helpers.php
 *
 * Fonctions globales chargées au démarrage (via composer.json "files"
 * ou manuellement dans index.php). Indépendant de l'autoload de classes.
 */

declare(strict_types=1);

if (!function_exists('e')) {
    /**
     * Échappe une valeur pour affichage HTML sûr (anti-XSS).
     * À utiliser dans TOUS les templates autour des données dynamiques.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('asset')) {
    /**
     * Génère l'URL d'un asset statique, préfixée par BASE_PATH.
     * Ex. asset('css/styles.css') => '/cityalert/public/assets/css/styles.css'
     */
    function asset(string $path): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        return $base . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Génère une URL interne, préfixée par BASE_PATH et routée via index.php
     * (pour fonctionner SANS .htaccess / mod_rewrite).
     * Ex. url('/signalements/5') => '/cityalert/public/index.php/signalements/5'
     *     url('/')               => '/cityalert/public/'
     */
    function url(string $path = '/'): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        $path = '/' . ltrim($path, '/');

        // La racine pointe simplement sur le dossier (index.php est le fichier par défaut)
        if ($path === '/') {
            return $base === '' ? '/' : $base . '/';
        }

        // Sépare le chemin de l'éventuelle query string (?a=b)
        $query = '';
        if (str_contains($path, '?')) {
            [$path, $query] = explode('?', $path, 2);
            $query = '?' . $query;
        }

        return $base . '/index.php' . $path . $query;
    }
}

if (!function_exists('uploadUrl')) {
    /**
     * Génère l'URL publique d'un fichier uploadé, préfixée par BASE_PATH.
     * Ex. uploadUrl('/uploads/signalements/abc.jpg')
     *     => '/cityalert/public/uploads/signalements/abc.jpg'
     */
    function uploadUrl(string $chemin): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        return $base . '/' . ltrim($chemin, '/');
    }
}

if (!function_exists('old')) {
    /**
     * Récupère une ancienne valeur de formulaire (après erreur de validation).
     *
     * @param array<string, mixed> $source
     */
    function old(string $key, array $source = [], string $default = ''): string
    {
        return e($source[$key] ?? $default);
    }
}
