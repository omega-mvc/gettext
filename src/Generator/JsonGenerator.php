<?php

declare(strict_types=1);

namespace Gettext\Generator;

use Gettext\Headers;
use Gettext\Translation;
use Gettext\Translations;

use function array_unshift;
use function implode;
use function is_array;
use function json_encode;

/**
 * Class JsonGenerator
 *
 * Generates a JSON representation of gettext translations.
 *
 * This final class extends `AbstractGenerator` and implements `generateString()`
 * to produce a JSON string containing all translations in a structured format.
 * You can configure JSON encoding options using the `jsonOptions()` method.
 *
 * Example usage:
 * ```php
 * $generator = new JsonGenerator();
 * $generator->jsonOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
 * $json = $generator->generateString($translations);
 * file_put_contents('locale/en/messages.json', $json);
 * ```
 */
final class JsonGenerator extends AbstractGenerator
{
    /** @var int JSON encoding options (flags from json_encode) */
    private int $jsonOptions = 0;

    /**
     * Set JSON encoding options.
     *
     * @param int $jsonOptions Flags compatible with `json_encode()`
     * @return self Returns self for method chaining
     */
    public function jsonOptions(int $jsonOptions): self
    {
        $this->jsonOptions = $jsonOptions;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function generateString(Translations $translations): string
    {
        $array = $this->generateArray($translations);

        return json_encode($array, $this->jsonOptions);
    }

    /**
     * Converts the given translations into a structured array.
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
            if (!$translation->getTranslation() || $translation->isDisabled()) {
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
}
