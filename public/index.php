<?php
/**
 * public/index.php
 *
 * Point d'entrée unique (Front Controller).
 * Toutes les requêtes HTTP passent ici grâce au .htaccess.
 *
 * Pipeline :
 *   1. Charge la config (.env → constantes)
 *   2. Charge l'autoload PSR-4 (composer)
 *   3. Démarre la session
 *   4. Construit la requête
 *   5. Dispatche via le routeur
 *   6. Envoie la réponse
 */

declare(strict_types=1);

// 1. Configuration
require_once __DIR__ . '/../config/config.php';

// 1bis. Helpers globaux (fonction e(), asset(), old()...)
require_once __DIR__ . '/../src/helpers.php';

// 2. Autoload
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    // Fallback : autoloader manuel PSR-4 si Composer n'a pas été lancé
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $file     = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
} else {
    require $autoload;
}

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

// 3. Session
Session::start();

// 4. Requête
$request = Request::capture();

// 5. Routage
try {
    /** @var \App\Core\Router $router */
    $router   = require __DIR__ . '/../config/routes.php';
    $response = $router->dispatch($request);
} catch (\Throwable $e) {
    if (APP_DEBUG) {
        $msg      = '<h1>Erreur 500</h1>'
                  . '<p><strong>' . htmlspecialchars($e->getMessage()) . '</strong></p>'
                  . '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        $response = Response::html($msg, 500);
    } else {
        $response = Response::html('<h1>Erreur serveur</h1>', 500);
    }
    error_log('[CityAlert] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
}

// 6. Envoi
$response->send();
