<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Request — encapsulation simple de la requête HTTP.
 *
 * Évite l'usage direct des superglobales dans les contrôleurs.
 * On peut tester plus facilement et la sémantique est plus claire.
 */
final class Request
{
    /** @var array<string, mixed> */
    public readonly array $query;
    /** @var array<string, mixed> */
    public readonly array $post;
    /** @var array<string, mixed> */
    public readonly array $files;
    /** @var array<string, mixed> */
    public readonly array $cookies;
    /** @var array<string, mixed> */
    public readonly array $server;

    public readonly string $method;
    public readonly string $path;

    public function __construct(
        ?array $query   = null,
        ?array $post    = null,
        ?array $files   = null,
        ?array $cookies = null,
        ?array $server  = null,
    ) {
        $this->query   = $query   ?? $_GET    ?? [];
        $this->post    = $post    ?? $_POST   ?? [];
        $this->files   = $files   ?? $_FILES  ?? [];
        $this->cookies = $cookies ?? $_COOKIE ?? [];
        $this->server  = $server  ?? $_SERVER ?? [];

        $this->method  = strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
        $this->path    = $this->extractPath();
    }

    public static function capture(): self
    {
        return new self();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed>|null */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name): ?string
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value      = $this->server[$normalized] ?? null;
        return $value !== null ? (string) $value : null;
    }

    public function isAjax(): bool
    {
        return strcasecmp((string) $this->header('X-Requested-With'), 'XMLHttpRequest') === 0;
    }

    public function expectsJson(): bool
    {
        $accept = (string) $this->header('Accept');
        return $this->isAjax() || str_contains($accept, 'application/json');
    }

    /**
     * Récupère le token CSRF, qu'il soit en POST (formulaires) ou en header
     * (requêtes AJAX type fetch). Utilisé par les contrôleurs.
     */
    public function csrfToken(): ?string
    {
        $fromPost = $this->post['csrf'] ?? null;
        if ($fromPost !== null) {
            return (string) $fromPost;
        }
        return $this->header('X-CSRF-Token');
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    private function extractPath(): string
    {
        // Cas 1 : routage via index.php/chemin → Apache fournit PATH_INFO
        $pathInfo = $this->server['PATH_INFO'] ?? null;
        if ($pathInfo !== null && $pathInfo !== '') {
            return '/' . trim((string) $pathInfo, '/');
        }

        // Cas 2 : à partir de REQUEST_URI (avec ou sans .htaccess)
        $uri  = (string) ($this->server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Retire le préfixe du sous-dossier (ex. /cityalert/public)
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        // Retire un éventuel /index.php résiduel
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php'));
        }

        return '/' . trim($path, '/');
    }
}
