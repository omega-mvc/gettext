<?php

declare(strict_types=1);

namespace Gettext\Loader;

use Exception;
use Gettext\Headers;
use Gettext\Translation;
use Gettext\Translations;

/**
 * Base class with common functions for all loaders.
 */
abstract class Loader implements LoaderInterface
{
    public function loadFile(string $filename, ?Translations $translations = null): Translations
    {
        $string = static::readFile($filename);

        return $this->loadString($string, $translations);
    }

    public function loadString(string $string, ?Translations $translations = null): Translations
    {
        return $translations ?: $this->createTranslations();
    }

    protected function createTranslations(): Translations
    {
        return Translations::create();
    }

    protected function createTranslation(?string $context, string $original, ?string $plural = null): Translation
    {
        $translation = Translation::create($context, $original);

        if (isset($plural)) {
            $translation->setPlural($plural);
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
                        $translation->translate((string) $singular);
                    }

                    if ($plurals !== []) {
                        $translation->translatePlural(...$plurals);
                    }
                } elseif (is_scalar($value)) {
                    $translation->translate((string) $value);
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
