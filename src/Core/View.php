<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * View — moteur de templates PHP natif (pas de Twig/Blade).
 *
 * - Templates dans /templates/
 * - Layout par défaut : layout/base.php
 * - Helper d'échappement : e()
 *
 * Usage :
 *   echo View::render('signalement/index', ['signalements' => $list]);
 */
final class View
{
    private static string $layout = 'layout/base';

    /**
     * Rend un template avec ses variables, optionnellement encapsulé dans un layout.
     *
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = self::capture($template, $data);
        $layout  = $layout ?? self::$layout;

        if ($layout === '' || $layout === 'none') {
            return $content;
        }
        return self::capture($layout, array_merge($data, ['content' => $content]));
    }

    /** @param array<string, mixed> $data */
    private static function capture(string $template, array $data): string
    {
        $file = TEMPLATE_PATH . '/' . ltrim($template, '/') . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Template introuvable : {$template}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    public static function setLayout(string $layout): void
    {
        self::$layout = $layout;
    }
}
