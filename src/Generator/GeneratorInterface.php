<?php

declare(strict_types=1);

namespace Gettext\Generator;

use Gettext\Translations;

/**
 * Interface GeneratorInterface
 *
 * Defines the contract for classes that can generate gettext translation files
 * from a `Translations` instance into different formats (PO, MO, JSON, etc.).
 *
 * Implementing classes are responsible for taking a collection of translations
 * and producing either a file on disk or a string representation of the translations.
 */
interface GeneratorInterface
{
    /**
     * Generate a translation file from a `Translations` instance.
     *
     * The output file will contain all translations formatted according to
     * the specific format handled by the implementing class.
     *
     * @param Translations $translations The translations collection to export.
     * @param string $filename The path to the file to create. If the file exists, it may be overwritten.
     * @return bool Returns true on success, false on failure.
     */
    public function generateFile(Translations $translations, string $filename): bool;

    /**
     * Generate a string representation of the translations.
     *
     * This method allows you to obtain the output as a string instead of
     * writing it directly to a file, which can be useful for testing,
     * caching, or sending translations over a network.
     *
     * @param Translations $translations The translations collection to export.
     * @return string The formatted translations as a string.
     */
    public function generateString(Translations $translations): string;
}
