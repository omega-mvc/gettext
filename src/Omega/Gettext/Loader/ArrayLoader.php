<?php

declare(strict_types=1);

namespace Omega\Gettext\Loader;

use BadMethodCallException;
use Exception;
use Omega\Gettext\Translations;

use function is_array;

/**
 * Class to load a array file
 */
final class ArrayLoader extends Loader
{
    public function loadFile(string $filename, ?Translations $translations = null): Translations
    {
        $array = self::includeSafe($filename);

        return $this->loadArray($array, $translations);
    }

    public function loadString(string $string, ?Translations $translations = null): Translations
    {
        throw new BadMethodCallException('Arrays cannot be loaded from string. Use ArrayLoader::loadFile() instead');
    }

    /**
     * @return array<array-key, mixed>
      * @param string $filename Path of the PHP file to include.
      * @return array<array-key, mixed> The returned array.
      * @throws \Exception If the file does not return an array.
     */
    private static function includeSafe(string $filename): array
    {
        $array = include $filename;

        if (!is_array($array)) {
            throw new Exception("Invalid translations file '$filename': it must return an array");
        }

        return $array;
    }

    /**
     * @param array<array-key, mixed> $array
     */
    public function loadArray(array $array, ?Translations $translations = null): Translations
    {
        if (!$translations) {
            $translations = $this->createTranslations();
        }

        $this->loadArrayData($array, $translations);

        return $translations;
    }
}
