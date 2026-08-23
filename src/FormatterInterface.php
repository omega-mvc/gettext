<?php

declare(strict_types=1);

namespace Gettext;

/**
 * Interface used by formatters.
 */
interface FormatterInterface
{
    /**
     * @param list<mixed> $args
     */
    public function format(string $text, array $args): string;
}
