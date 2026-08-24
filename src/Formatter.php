<?php

declare(strict_types=1);

namespace Gettext;

use InvalidArgumentException;

use function get_debug_type;
use function is_array;
use function is_scalar;
use function strtr;
use function vsprintf;

class Formatter implements FormatterInterface
{
    public function format(string $text, array $args): string
    {
        if ($args === []) {
            return $text;
        }

        if (is_array($args[0])) {
            $replacements = [];

            foreach ($args[0] as $key => $value) {
                if (!is_scalar($value)) {
                    throw new InvalidArgumentException(sprintf(
                        'Formatter replacements must be scalars, %s given',
                        get_debug_type($value)
                    ));
                }

                $replacements[$key] = (string) $value;
            }

            return strtr($text, $replacements);
        }

        $values = [];

        foreach ($args as $arg) {
            if (!is_scalar($arg) && $arg !== null) {
                throw new InvalidArgumentException(sprintf(
                    'Formatter arguments must be scalars, %s given',
                    get_debug_type($arg)
                ));
            }

            $values[] = $arg;
        }

        return vsprintf($text, $values);
    }
}
