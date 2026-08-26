<?php

declare(strict_types=1);

namespace Omega\Gettext\Loader;

use Omega\Gettext\Translation;
use Omega\Gettext\Translations;

use function array_map;
use function current;
use function explode;
use function implode;
use function intval;
use function next;
use function preg_match;
use function preg_split;
use function strpos;
use function strtr;
use function substr;
use function trim;

/**
 * Class to load a PO file.
 */
final class PoLoader extends Loader
{
    public function loadString(string $string, ?Translations $translations = null): Translations
    {
        $translations = parent::loadString($string, $translations);

        $lines = explode("\n", $string);
        $line = current($lines);
        $translation = $this->createTranslation(null, '');

        while ($line !== false) {
            $line = trim($line);
            $nextLine = next($lines);

            // Treat empty comments as empty lines https://github.com/php-gettext/Gettext/pull/296
            if ($line === '#') {
                $line = '';
            }

            //Multiline
            while (
                substr($line, -1, 1) === '"'
                && $nextLine !== false
                && (substr(trim($nextLine), 0, 1) === '"' || substr(trim($nextLine), 0, 4) === '#~ "')
            ) {
                if (substr(trim($nextLine), 0, 1) === '"') { // Normal multiline
                    $line = substr($line, 0, -1) . substr(trim($nextLine), 1);
                } elseif (substr(trim($nextLine), 0, 4) === '#~ "') { // Disabled multiline
                    $line = substr($line, 0, -1) . substr(trim($nextLine), 4);
                }
                $nextLine = next($lines);
            }

            //End of translation
            if ($line === '') {
                if (!self::isEmpty($translation)) {
                    $translations->add($translation);
                }

                $translation = $this->createTranslation(null, '');
                $line = $nextLine;
                continue;
            }

            $splitLine = preg_split('/\s+/', $line, 2) ?: [''];
            $key = $splitLine[0];
            $data = $splitLine[1] ?? '';

            if ($key === '#~') {
                $translation->disabled = true;

                $splitLine = preg_split('/\s+/', $data, 2) ?: [''];
                $key = $splitLine[0];
                $data = $splitLine[1] ?? '';
            }

            if ($data === '') {
                $line = $nextLine;
                continue;
            }

            switch ($key) {
                case '#':
                    $translation->getComments()->add($data);
                    break;
                case '#.':
                    $translation->getExtractedComments()->add($data);
                    break;
                case '#,':
                    foreach (array_map('trim', explode(',', trim($data))) as $value) {
                        $translation->getFlags()->add($value);
                    }
                    break;
                case '#:':
                    foreach (preg_split('/\s+/', trim($data)) ?: [] as $value) {
                        if (preg_match('/^(.+)(:(\d*))?$/U', $value, $matches)) {
                            $line = isset($matches[3]) ? intval($matches[3]) : null;
                            $translation->getReferences()->add($matches[1], $line);
                        }
                    }
                    break;
                case 'msgctxt':
                    $translation = $translation->withContext(self::decode($data));
                    break;
                case 'msgid':
                    $translation = $translation->withOriginal(self::decode($data));
                    break;
                case 'msgid_plural':
                    $translation->plural = self::decode($data);
                    break;
                case 'msgstr':
                case 'msgstr[0]':
                    $translation->translation = self::decode($data);
                    break;
                case 'msgstr[1]':
                    $translation->translatePlural(self::decode($data));
                    break;
                default:
                    if (strpos($key, 'msgstr[') === 0) {
                        $p = $translation->getPluralTranslations();
                        $p[] = self::decode($data);

                        $translation->translatePlural(...$p);
                        break;
                    }
                    break;
            }

            $line = $nextLine;
        }

        if (!self::isEmpty($translation)) {
            $translations->add($translation);
        }

        //Headers
        $translation = $translations->find(null, '');

        if (!$translation) {
            return $translations;
        }

        $translations->remove($translation);

        $description = $translation->getComments()->toArray();

        if (!empty($description)) {
            $translations->description = implode("\n", $description);
        }

        $flags = $translation->getFlags()->toArray();

        if (!empty($flags)) {
            $translations->getFlags()->add(...$flags);
        }

        $headers = $translations->getHeaders();

        foreach (self::parseHeaders($translation->translation) as $name => $value) {
            $headers->set($name, $value);
        }

        return $translations;
    }

    /**
     * @return array<string, string>
      * @param string|null $string Raw msgstr of the header entry.
      * @return array<string, string> Header names mapped to their values.
     */
    private static function parseHeaders(?string $string): array
    {
        if (empty($string)) {
            return [];
        }

        $headers = [];
        $lines = explode("\n", $string);
        $name = null;

        foreach ($lines as $line) {
            $line = self::decode($line);

            if ($line === '') {
                continue;
            }

            // Checks if it is a header definition line.
            // Useful for distinguishing between header definitions and possible continuations of a header entry.
            if (preg_match('/^[\w-]+:/', $line)) {
                $pieces = array_map('trim', explode(':', $line, 2));
                [$name, $value] = $pieces;

                $headers[$name] = $value;
                continue;
            }

            $value = $headers[$name] ?? '';
            $headers[$name] = $value . $line;
        }

        return $headers;
    }

    /**
     * Convert a string from its PO representation.
      * @param string $value Text in .po representation (quoted and escaped).
      * @return string The decoded plain text.
     */
    public static function decode(string $value): string
    {
        if (!$value) {
            return '';
        }

        if ($value[0] === '"') {
            $value = substr($value, 1, -1);
        }

        return strtr(
            $value,
            [
                '\\\\' => '\\',
                '\\a' => "\x07",
                '\\b' => "\x08",
                '\\t' => "\t",
                '\\n' => "\n",
                '\\v' => "\x0b",
                '\\f' => "\x0c",
                '\\r' => "\r",
                '\\"' => '"',
            ]
        );
    }

    /**
     * Checks whether an entry carries no information at all.
     *
     * @param Translation $translation Entry to inspect.
     * @return bool True when both original and translation are empty.
     */
    private static function isEmpty(Translation $translation): bool
    {
        if (!empty($translation->getOriginal())) {
            return false;
        }

        if (!empty($translation->translation)) {
            return false;
        }

        return true;
    }
}
