<?php

declare(strict_types=1);

namespace Gettext\Generator;

use Gettext\Headers;
use Gettext\Translation;
use Gettext\Translations;

use function array_is_list;
use function array_keys;
use function array_unshift;
use function count;
use function function_exists;
use function implode;
use function is_array;
use function range;
use function str_repeat;
use function var_export;

/**
 * Class ArrayGenerator
 *
 * Generates a PHP array representation of gettext translations.
 *
 * This final class extends `AbstractGenerator` and implements `generateString()`
 * to produce a PHP file containing all translations in a structured array format.
 * It supports optional pretty-printing, strict types declaration, and inclusion of empty translations.
 *
 * Example usage:
 * ```php
 * $generator = new ArrayGenerator(['pretty' => true, 'strictTypes' => true]);
 * $phpCode = $generator->generateString($translations);
 * file_put_contents('locale/en/messages.php', $phpCode);
 * ```
 */
final class ArrayGenerator extends AbstractGenerator
{
    /** String used for indentation in pretty-printed arrays. */
    private const string PRETTY_INDENT = '    ';

    /** @var bool Whether to include translations that are empty. */
    private bool $includeEmpty;

    /** @var bool Whether to generate code with `declare(strict_types=1)`. */
    private bool $strictTypes;

    /** @var bool Whether to pretty-print the generated array. */
    private bool $pretty;

    /**
     * Constructs a new ArrayGenerator.
     *
     * @param array|null $options Optional configuration:
     *   - 'includeEmpty' (bool): include empty translations, default false
     *   - 'strictTypes' (bool): add `declare(strict_types=1)`, default false
     *   - 'pretty' (bool): pretty-print the array, default false
     * @return void
     */
    public function __construct(?array $options = null)
    {
        $this->includeEmpty = (bool) ($options['includeEmpty'] ?? false);
        $this->strictTypes  = (bool) ($options['strictTypes'] ?? false);
        $this->pretty       = (bool) ($options['pretty'] ?? false);
    }

    /**
     * {@inheritdoc}
     */
    public function generateString(Translations $translations): string
    {
        $array  = $this->generateArray($translations);
        $result = '<?php';

        if ($this->pretty) {
            $result .= $this->strictTypes ? "\n\ndeclare(strict_types=1);\n\n" : "\n\n";
        } else {
            $result .= $this->strictTypes ? ' declare(strict_types=1); ' : ' ';
        }

        return $result . 'return ' . ($this->pretty ? self::prettyExport($array) : (var_export($array, true) . ';'));
    }

    /**
     * Generates an array representation of the given translations.
     *
     * @param Translations $translations The translations to convert
     * @return array Structured array containing domain, plural forms, and messages
     */
    public function generateArray(Translations $translations): array
    {
        $pluralForm = $translations->getHeaders()->getPluralForm();
        $pluralSize = is_array($pluralForm) ? ($pluralForm[0] - 1) : null;
        $messages   = [];

        foreach ($translations as $translation) {
            if ((!$this->includeEmpty && !$translation->getTranslation()) || $translation->isDisabled()) {
                continue;
            }

            $context  = $translation->getContext() ?: '';
            $original = $translation->getOriginal();

            if (!isset($messages[$context])) {
                $messages[$context] = [];
            }

            if (self::hasPluralTranslations($translation)) {
                $messages[$context][$original] = $translation->getPluralTranslations($pluralSize);
                array_unshift($messages[$context][$original], $translation->getTranslation());
            } else {
                $messages[$context][$original] = $translation->getTranslation();
            }
        }

        return [
            'domain'       => $translations->getDomain(),
            'plural-forms' => $translations->getHeaders()->get(Headers::HEADER_PLURAL),
            'messages'     => $messages,
        ];
    }

    /**
     * Checks if a translation has plural forms.
     *
     * @param Translation $translation The translation to check
     * @return bool True if the translation has plural translations, false otherwise
     */
    private static function hasPluralTranslations(Translation $translation): bool
    {
        return implode('', $translation->getPluralTranslations()) !== '';
    }

    /**
     * Pretty-prints an array as a PHP string.
     *
     * @param array $array The array to pretty-print
     * @return string PHP code string
     */
    private static function prettyExport(array $array): string
    {
        return self::prettyExportArray($array, 0) . ";\n";
    }

    /**
     * Recursively pretty-prints an array with indentation.
     *
     * @param array $array The array to pretty-print
     * @param int $depth Current recursion depth for indentation
     * @return string Pretty-printed PHP code
     */

    private static function prettyExportArray(array $array, int $depth): string
    {
        if ($array === []) {
            return '[]';
        }
        $result = '[';
        $isList = self::isList($array);

        foreach ($array as $key => $value) {
            $result .= "\n" . str_repeat(self::PRETTY_INDENT, $depth + 1);
            if (!$isList) {
                $result .= var_export($key, true) . ' => ';
            }

            if (is_array($value)) {
                $result .= self::prettyExportArray($value, $depth + 1);
            } else {
                $result .= self::prettyExportScalar($value);
            }

            $result .= ',';
        }

        return $result . "\n" . str_repeat(self::PRETTY_INDENT, $depth) . ']';
    }

    /**
     * Pretty-prints a scalar value.
     *
     * @param mixed $value Scalar value to export
     * @return string PHP representation of the scalar
     */
    private static function prettyExportScalar($value): string
    {
        return $value === null ? 'null' : var_export($value, true);
    }

    /**
     * Checks whether an array is a list (sequential keys starting from 0).
     *
     * @param array $value Array to check
     * @return bool True if the array is a list, false otherwise
     */
    private static function isList(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        if (function_exists('array_is_list')) {
            return array_is_list($value);
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
