<?php
/**
 * config/config.php
 *
 * Chargement minimaliste du .env (sans dépendance externe)
 * et exposition des constantes globales.
 */

declare(strict_types=1);

// ---------- Mini parseur .env natif ----------
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\"'");
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }
    return match (strtolower((string) $value)) {
        'true', '(true)'   => true,
        'false', '(false)' => false,
        'null', '(null)'   => null,
        default            => $value,
    };
}

// ---------- Chargement ----------
loadEnv(dirname(__DIR__) . '/.env');

// ---------- Constantes ----------
define('APP_NAME',  env('APP_NAME',  'CityAlert'));
define('APP_ENV',   env('APP_ENV',   'production'));
define('APP_DEBUG', (bool) env('APP_DEBUG', false));
define('APP_URL',   env('APP_URL',   'http://localhost:8888/cityalert/public/'));
define('APP_SECRET', env('APP_SECRET', 'changez-moi'));

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) env('DB_PORT', 8889));
define('DB_NAME', env('DB_NAME', 'cityalert'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', 'root'));

define('SESSION_LIFETIME',     (int) env('SESSION_LIFETIME', 3600));
define('REMEMBER_ME_LIFETIME', (int) env('REMEMBER_ME_LIFETIME', 2592000));

define('MAIL_FROM',      env('MAIL_FROM', 'noreply@cityalert.sn'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'CityAlert'));

define('SMTP_HOST',     env('SMTP_HOST',     'smtp.gmail.com'));
define('SMTP_PORT',     (int) env('SMTP_PORT', 587));
define('SMTP_USER',     env('SMTP_USER',     ''));
define('SMTP_PASS',     env('SMTP_PASS',     ''));
define('SMTP_SECURE',   env('SMTP_SECURE',   'tls'));

define('UPLOAD_MAX_SIZE',      (int) env('UPLOAD_MAX_SIZE', 5_242_880));
define('UPLOAD_ALLOWED_TYPES', explode(',', (string) env('UPLOAD_ALLOWED_TYPES', 'image/jpeg,image/png,image/webp')));

define('ROOT_PATH',     dirname(__DIR__));
define('PUBLIC_PATH',   ROOT_PATH . '/public');
define('UPLOAD_PATH',   PUBLIC_PATH . '/uploads');
define('TEMPLATE_PATH', ROOT_PATH . '/templates');

/*
 * BASE_PATH : préfixe d'URL quand le projet n'est pas à la racine du domaine.
 * Détecté automatiquement à partir de l'emplacement de public/index.php.
 * Ex. si le site est sur http://localhost:8888/cityalert/public/
 *     alors BASE_PATH = '/cityalert/public'
 * À la racine d'un domaine (prod), BASE_PATH vaut '' (chaîne vide).
 */
$scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
$scriptDir = rtrim($scriptDir, '/');
define('BASE_PATH', $scriptDir === '/' ? '' : $scriptDir);

// ---------- Affichage des erreurs (selon environnement) ----------
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ---------- Timezone ----------
date_default_timezone_set('Africa/Dakar');
