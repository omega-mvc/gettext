<?php

declare(strict_types=1);

namespace Omega\Gettext;

/**
 * Contract for classes rendering translated texts by replacing placeholders.
 *
 * Implementations decide the placeholder syntax; the built-in Formatter
 * supports both printf-style arguments and name/value replacement maps.
 */
interface FormatterInterface
{
    /**
     * Injects the given arguments into the translated text.
     *
     * @param string $text The already translated text containing placeholders.
     * @param list<mixed> $args Replacement values, in argument order.
     *
     * @return string The rendered text.
     */
    public function format(string $text, array $args): string;
}
