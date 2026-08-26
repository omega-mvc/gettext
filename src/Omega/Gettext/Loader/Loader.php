<?php

declare(strict_types=1);

namespace Omega\Gettext\Loader;

use Exception;
use Omega\Gettext\Headers;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;

use function array_shift;
use function file_get_contents;
use function is_array;
use function is_scalar;
use function is_string;

/**
 * Base class with common functions for all loaders.
 */
abstract class Loader implements LoaderInterface
{
    /**
     * @param string $filename Path of the file to read.
     * @param Translations|null $translations Catalog to populate; a new one is created when null.
     * @return Translations The populated catalog.
     * @throws \Exception If the file cannot be read.
     */
    public function loadFile(string $filename, ?Translations $translations = null): Translations
    {
        $string = static::readFile($filename);

        return $this->loadString($string, $translations);
    }

    /**
     * @param string $string Raw content to parse; the base implementation ignores it.
     * @param Translations|null $translations Catalog to populate; a new one is created when null.
     * @return Translations The catalog passed in, or a fresh empty one.
     */
    public function loadString(string $string, ?Translations $translations = null): Translations
    {
        return $translations ?: $this->createTranslations();
    }

    /**
     * Factory creating the catalog used by loadString() when none is injected.
     *
     * @return Translations A fresh empty catalog.
     */
    protected function createTranslations(): Translations
    {
        return Translations::create();
    }

    /**
     * Factory building a Translation instance.
     *
     * @param string|null $context Gettext context, or null for the default one.
     * @param string $original Source string (msgid).
     * @param string|null $plural Optional source plural string (msgid_plural).
     * @return Translation The created instance.
     */
    protected function createTranslation(?string $context, string $original, ?string $plural = null): Translation
    {
        $translation = Translation::create($context, $original);

        if (isset($plural)) {
            $translation->plural = $plural;
        }

        return $translation;
    }

    /**
     * Loads messages from the array structure produced by ArrayGenerator
     * and JsonGenerator.
     *
     * @param array<array-key, mixed> $array
     */
    protected function loadArrayData(array $array, Translations $translations): void
    {
        $messages = $array['messages'] ?? [];

        if (!is_array($messages)) {
            return;
        }

        foreach ($messages as $contextKey => $contextTranslations) {
            if (!is_array($contextTranslations)) {
                continue;
            }

            $context = $contextKey === '' ? null : (string) $contextKey;

            foreach ($contextTranslations as $originalKey => $value) {
                $original = (string) $originalKey;

                if ($original === '') {
                    continue;
                }

                $translation = $this->createTranslation($context, $original);
                $translations->add($translation);

                if (is_array($value)) {
                    $singular = array_shift($value);
                    $plurals = [];

                    foreach ($value as $pluralValue) {
                        if (is_scalar($pluralValue)) {
                            $plurals[] = (string) $pluralValue;
                        }
                    }

                    if (is_scalar($singular)) {
                        $translation->translation = (string) $singular;
                    }

                    if ($plurals !== []) {
                        $translation->translatePlural(...$plurals);
                    }
                } elseif (is_scalar($value)) {
                    $translation->translation = (string) $value;
                }
            }
        }

        $domain = $array['domain'] ?? null;

        if (is_string($domain) && $domain !== '') {
            $translations->setDomain($domain);
        }

        $pluralForms = $array['plural-forms'] ?? null;

        if (is_string($pluralForms) && $pluralForms !== '') {
            $translations->getHeaders()->set(Headers::HEADER_PLURAL, $pluralForms);
        }
    }

    /**
     * Reads and returns the content of a file.
      * @param string $file Path of the file to read.
      * @return string The whole file content.
      * @throws \Exception If the file cannot be read (missing or unreadable).
     */
    protected static function readFile(string $file): string
    {
        $content = @file_get_contents($file);

        if (false === $content) {
            throw new Exception("Cannot read the file '$file', probably permissions");
        }

        return $content;
    }
}
