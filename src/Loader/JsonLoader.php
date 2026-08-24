<?php

declare(strict_types=1);

namespace Gettext\Loader;

use Exception;
use Gettext\Translations;

/**
 * Class to load a json file
 */
final class JsonLoader extends Loader
{
    public function loadString(string $string, ?Translations $translations = null): Translations
    {
        $array = json_decode($string, true);

        if (!is_array($array)) {
            throw new Exception('Invalid translations file: it must contain a JSON object');
        }

        return $this->loadArray($array, $translations);
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
