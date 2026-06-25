<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session — wrapper $_SESSION sécurisé.
 *
 * Centralise :
 *  - Démarrage configuré (cookie HttpOnly + SameSite)
 *  - Token CSRF (génération + validation)
 *  - Messages flash (success, error, info)
 *  - Stockage de l'identifiant utilisateur connecté
 */
final class Session
{
    private const KEY_USER  = '__user_id';
    private const KEY_CSRF  = '__csrf';
    private const KEY_FLASH = '__flash';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_set_cookie_params([
            'lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600,
            'path'     => '/',
            'secure'   => false,                        // true en HTTPS prod
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    // ---------- Utilisateur connecté ----------
    public static function setUserId(int $id): void
    {
        self::start();
        $_SESSION[self::KEY_USER] = $id;
        // Sécurité : régénérer l'ID de session à chaque login
        session_regenerate_id(true);
    }

    public static function getUserId(): ?int
    {
        self::start();
        $id = $_SESSION[self::KEY_USER] ?? null;
        return $id !== null ? (int) $id : null;
    }

    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ---------- CSRF ----------
    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION[self::KEY_CSRF])) {
            $_SESSION[self::KEY_CSRF] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION[self::KEY_CSRF];
    }

    public static function verifyCsrf(?string $token): bool
    {
        if ($token === null) return false;
        self::start();
        $expected = (string) ($_SESSION[self::KEY_CSRF] ?? '');
        return $expected !== '' && hash_equals($expected, $token);
    }

    // ---------- Flash ----------
    public static function flash(string $type, string $message): void
    {
        self::start();
        $_SESSION[self::KEY_FLASH][$type][] = $message;
    }

    /** @return array<string, string[]> */
    public static function consumeFlash(): array
    {
        self::start();
        $f = $_SESSION[self::KEY_FLASH] ?? [];
        unset($_SESSION[self::KEY_FLASH]);
        return $f;
    }
}
