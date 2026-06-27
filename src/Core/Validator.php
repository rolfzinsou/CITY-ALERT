<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validator — règles de validation simples mais utilisables partout.
 *
 * Usage :
 *   $v = new Validator($request->post);
 *   $v->required('email')->email('email');
 *   $v->required('telephone')->telephoneSn('telephone');
 *   $v->required('mot_de_passe')->minLength('mot_de_passe', 8);
 *   if ($v->fails()) { ...$v->errors() }
 */
final class Validator
{
    /** @var array<string, string[]> */
    private array $errors = [];

    /** @param array<string, mixed> $data */
    public function __construct(private array $data) {}

    public function required(string $field, string $message = 'Champ obligatoire'): self
    {
        $v = $this->data[$field] ?? null;
        if ($v === null || $v === '' || (is_array($v) && empty($v))) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function email(string $field, string $message = 'Adresse email invalide'): self
    {
        $v = (string) ($this->data[$field] ?? '');
        if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    /**
     * Téléphone format Sénégal : +221 suivi de 9 chiffres.
     */
    public function telephoneSn(string $field, string $message = 'Téléphone invalide (format +221XXXXXXXXX)'): self
    {
        $v = (string) ($this->data[$field] ?? '');
        if ($v !== '' && !preg_match('/^\+221[0-9]{9}$/', $v)) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function minLength(string $field, int $min, ?string $message = null): self
    {
        $v = (string) ($this->data[$field] ?? '');
        if ($v !== '' && mb_strlen($v) < $min) {
            $this->errors[$field][] = $message ?? "Doit faire au moins {$min} caractères";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, ?string $message = null): self
    {
        $v = (string) ($this->data[$field] ?? '');
        if (mb_strlen($v) > $max) {
            $this->errors[$field][] = $message ?? "Doit faire au plus {$max} caractères";
        }
        return $this;
    }

    public function sameAs(string $field, string $other, string $message = 'Les valeurs ne correspondent pas'): self
    {
        $a = $this->data[$field] ?? null;
        $b = $this->data[$other] ?? null;
        if ($a !== null && $b !== null && $a !== $b) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function inEnum(string $field, array $allowed, string $message = 'Valeur non autorisée'): self
    {
        $v = $this->data[$field] ?? null;
        if ($v !== null && !in_array($v, $allowed, true)) {
            $this->errors[$field][] = $message;
        }
        return $this;
    }

    public function fails(): bool      { return !empty($this->errors); }
    public function passes(): bool     { return empty($this->errors); }
    /** @return array<string, string[]> */
    public function errors(): array    { return $this->errors; }
    /** @return string[] */
    public function errorsFor(string $field): array { return $this->errors[$field] ?? []; }

    /** Première erreur pour chaque champ (utile pour affichage compact) */
    public function firstErrors(): array
    {
        return array_map(fn($e) => $e[0] ?? '', $this->errors);
    }
}
