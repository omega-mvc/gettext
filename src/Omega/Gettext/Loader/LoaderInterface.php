<?php

declare(strict_types=1);

namespace Omega\Gettext\Loader;

use Omega\Gettext\Translations;

interface LoaderInterface
{
    /**
     * Reads a file and loads every translation found in it.
     *
     * @param string $filename Path of the file to read.
     * @param Translations|null $translations Catalog to populate; a new one is created when null.
     * @return Translations The populated catalog.
     */
    public function loadFile(string $filename, ?Translations $translations = null): Translations;

    /**
     * Parses raw content and loads every translation found in it.
     *
     * @param string $string Raw content to parse.
     * @param Translations|null $translations Catalog to populate; a new one is created when null.
     * @return Translations The populated catalog.
     */
    public function loadString(string $string, ?Translations $translations = null): Translations;
}
