<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Response — sortie HTTP.
 *
 * Fournit des fabriques claires : html, json, redirect, download.
 * Évite les `echo` et `header()` éparpillés dans les contrôleurs.
 */
final class Response
{
    /** @var array<string, string> */
    private array $headers = [];

    public function __construct(
        private string $body = '',
        private int $status  = 200,
    ) {}

    public static function html(string $html, int $status = 200): self
    {
        $r = new self($html, $status);
        $r->withHeader('Content-Type', 'text/html; charset=utf-8');
        return $r;
    }

    /** @param array<mixed>|object $data */
    public static function json(array|object $data, int $status = 200): self
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            $body   = '{"error":"json_encode_failed"}';
            $status = 500;
        }
        $r = new self($body, $status);
        $r->withHeader('Content-Type', 'application/json; charset=utf-8');
        return $r;
    }

    public static function redirect(string $url, int $status = 302): self
    {
        $r = new self('', $status);
        $r->withHeader('Location', $url);
        return $r;
    }

    public static function notFound(string $message = 'Page introuvable'): self
    {
        return self::html("<h1>404 — {$message}</h1>", 404);
    }

    public static function forbidden(string $message = 'Accès refusé'): self
    {
        return self::html("<h1>403 — {$message}</h1>", 403);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }
        }
        echo $this->body;
    }

    public function getStatus(): int { return $this->status; }
    public function getBody(): string { return $this->body; }
}
