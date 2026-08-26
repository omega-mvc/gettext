<?php

declare(strict_types=1);

namespace Omega\Gettext\Generator;

use Omega\Gettext\Translations;

use function file_put_contents;

/**
 * Class AbstractGenerator
 *
 * Provides a base implementation for translation generators.
 *
 * This abstract class implements the `GeneratorInterface` and provides a
 * common implementation for `generateFile()`, which writes the output
 * of `generateString()` to a file. Concrete subclasses only need to implement
 * `generateString()` to define the specific format (PO, MO, JSON, etc.).
 *
 * By extending this class, you ensure a consistent file-writing behavior
 * across all generators while allowing flexibility in string generation.
 */
abstract class AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
      * Writes the generated output to a file.
      *
      * @param Translations $translations The catalog to export.
      * @param string $filename Destination path; an existing file is overwritten.
      * @return bool True when the whole content was written successfully.
      * @throws \Exception If the concrete generator fails while rendering.
     */
    public function generateFile(Translations $translations, string $filename): bool
    {
        $content = $this->generateString($translations);

        return file_put_contents($filename, $content) !== false;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function generateString(Translations $translations): string;
}
