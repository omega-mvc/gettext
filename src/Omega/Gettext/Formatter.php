<?php

declare(strict_types=1);

namespace Omega\Gettext;

use InvalidArgumentException;

use function array_is_list;
use function get_debug_type;
use function is_array;
use function is_scalar;
use function sprintf;
use function strtr;
use function vsprintf;

/**
 * Renders translated texts by injecting arguments into placeholders.
 *
 * Two replacement styles are supported, mirroring the global gettext
 * helpers of the Omega ecosystem:
 *
 * - printf style: `__('Hello %s', 'world')` uses vsprintf();
 * - map style: `__('Hi %name', ['%name' => 'John'])` uses strtr().
 */
class Formatter implements FormatterInterface
{
    /**
     * Injects the arguments into the translated text.
     *
     * When the first argument is an array it is treated as a replacement
     * map applied with strtr(); otherwise the values are passed to vsprintf().
     *
     * @param string $text The already translated text containing placeholders.
     * @param array<array-key, mixed> $args Replacement values.
     *
     * @throws InvalidArgumentException If a value is not scalar (map style)
     *         or any argument is neither scalar nor null (printf style).
     *
     * @return string The rendered text.
     */
    public function format(string $text, array $args): string
    {
        if ($args === []) {
            return $text;
        }

        $first = $args[0] ?? null;

        if (!array_is_list($args) || is_array($first)) {
            $source = (is_array($first) && array_is_list($args)) ? $first : $args;
            $replacements = [];

            foreach ($source as $key => $value) {
                if (!is_scalar($value)) {
                    throw new InvalidArgumentException(sprintf(
                        'Formatter replacements must be scalars, %s given',
                        get_debug_type($value)
                    ));
                }

                $replacements[(string) $key] = (string) $value;
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
