<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Database — singleton PDO.
 *
 * Centralise la création et la réutilisation de la connexion à la base de données.
 * Les options PDO sont fixées une fois pour toutes ici :
 *  - Exceptions sur erreur (au lieu de codes silencieux)
 *  - Fetch en tableau associatif par défaut
 *  - Désactivation des "prepared statements" émulés (vraies requêtes préparées MySQL,
 *    plus sûres contre les injections)
 */
final class Database
{
    private static ?PDO $instance = null;

    /** Empêche l'instanciation directe : on passe par getInstance(). */
    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }

    private static function createConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            return new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                throw new RuntimeException("Connexion BDD échouée : {$e->getMessage()}", 0, $e);
            }
            throw new RuntimeException("Service indisponible.", 0, $e);
        }
    }

    /** Pour les tests : réinitialise la connexion (à n'utiliser qu'en environnement de test). */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
