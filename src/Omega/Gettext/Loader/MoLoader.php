<?php

declare(strict_types=1);

namespace Omega\Gettext\Loader;

use Exception;
use Omega\Gettext\Translations;

use function array_filter;
use function array_shift;
use function explode;
use function is_array;
use function is_int;
use function preg_split;
use function strlen;
use function substr;
use function unpack;

/**
 * Class to load a MO file.
 */
final class MoLoader extends Loader
{
    private string $string;
    private int $position = 0;
    private int $length = 0;

    private const MAGIC1 = -1794895138;
    private const MAGIC2 = -569244523;
    private const MAGIC3 = 2500072158;

    public function loadString(string $string, ?Translations $translations = null): Translations
    {
        $translations = parent::loadString($string, $translations);
        $this->init($string);

        $magic = $this->readInt('V');

        if (($magic === self::MAGIC1) || ($magic === self::MAGIC3)) { //to make sure it works for 64-bit platforms
            $byteOrder = 'V'; //low endian
        } elseif ($magic === (self::MAGIC2 & 0xFFFFFFFF)) {
            $byteOrder = 'N'; //big endian
        } else {
            throw new Exception('Not MO file');
        }

        $this->readInt($byteOrder);

        $total = $this->readInt($byteOrder); //total string count
        $originals = $this->readInt($byteOrder); //offset of original table
        $tran = $this->readInt($byteOrder); //offset of translation table

        $this->seekto($originals);
        $table_originals = $this->readIntArray($byteOrder, $total * 2);

        $this->seekto($tran);
        $table_translations = $this->readIntArray($byteOrder, $total * 2);

        for ($i = 0; $i < $total; ++$i) {
            $next = $i * 2;

            $this->seekto($table_originals[$next + 2]);
            $original = $this->read($table_originals[$next + 1]);

            $this->seekto($table_translations[$next + 2]);
            $translated = $this->read($table_translations[$next + 1]);

            // Headers
            if ($original === '') {
                foreach (explode("\n", $translated) as $headerLine) {
                    if ($headerLine === '') {
                        continue;
                    }

                    $headerChunks = preg_split('/:\s*/', $headerLine, 2) ?: [''];
                    $translations->getHeaders()->set($headerChunks[0], $headerChunks[1] ?? '');
                }

                continue;
            }

            $context = $plural = null;
            $chunks = explode("\x04", $original, 2);

            if (isset($chunks[1])) {
                [$context, $original] = $chunks;
            }

            $chunks = explode("\x00", $original, 2);

            if (isset($chunks[1])) {
                [$original, $plural] = $chunks;
            }

            $translation = $this->createTranslation($context, $original, $plural);
            $translations->add($translation);

            if ($translated === '') {
                continue;
            }

            if ($plural === null) {
                $translation->translate($translated);
                continue;
            }

            $v = explode("\x00", $translated);
            $translation->translate(array_shift($v));
            $translation->translatePlural(...array_filter($v));
        }

        return $translations;
    }

    /**
     * Resets the internal cursor over the given binary content.
     *
     * @param string $string Raw binary content of the .mo file.
     */
    private function init(string $string): void
    {
        $this->string = $string;
        $this->position = 0;
        $this->length = strlen($string);
    }

    /**
     * Consumes a chunk of bytes advancing the internal cursor.
     *
     * @param int $bytes Number of bytes to consume from the current position.
     * @return string The consumed chunk; shorter near the end of the data.
     */
    private function read(int $bytes): string
    {
        $data = substr($this->string, $this->position, $bytes);

        $this->seekTo($this->position + $bytes);

        return $data;
    }

    /**
     * Moves the internal cursor, clamping past-the-end requests.
     *
     * @param int $position Absolute position to jump to; clamped to the data length.
     */
    private function seekTo(int $position): void
    {
        $this->position = ($this->length < $position) ? $this->length : $position;
    }

    /**
     * Reads one unsigned 32-bit integer using the given byte order.
     *
     * @param string $byteOrder unpack() format code, V (little-endian) or N (big-endian).
     * @return int The decoded 32-bit integer, or 0 on malformed input.
     */
    private function readInt(string $byteOrder): int
    {
        $unpacked = unpack($byteOrder, $this->read(4));
        $value = $unpacked === false ? null : array_shift($unpacked);

        return is_int($value) ? $value : 0;
    }

    /**
     * Returns the unpacked table keeping the 1-based integer keys of unpack().
     *
     * @return array<int, int>
      * @param string $byteOrder unpack() format code, V (little-endian) or N (big-endian).
      * @param int $count Number of integers to decode.
      * @return array<int, int> Decoded integers keeping the 1-based keys of unpack().
     */
    private function readIntArray(string $byteOrder, int $count): array
    {
        $unpacked = unpack($byteOrder . $count, $this->read(4 * $count));
        $values = [];

        if (is_array($unpacked)) {
            foreach ($unpacked as $key => $value) {
                if (is_int($key) && is_int($value)) {
                    $values[$key] = $value;
                }
            }
        }

        return $values;
    }
}
