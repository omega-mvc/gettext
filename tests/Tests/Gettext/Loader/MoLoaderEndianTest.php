<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Loader;

use Exception;
use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\Loader\MoLoader;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(MoLoader::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
class MoLoaderEndianTest extends TestCase
{
    public function testBigEndianFilesAreLoaded(): void
    {
        $binary = self::makeMo([
            ['', "Language: it\n\nContent-Type: text/plain\n"],
            ['Hello', 'Ciao'],
        ], 'N');

        $translations = (new MoLoader())->loadString($binary);

        $this->assertSame('it', $translations->getHeaders()->get('Language'));
        $translation = $translations->find(null, 'Hello');

        $this->assertNotNull($translation);
        $this->assertSame('Ciao', $translation->translation);
    }

    public function testEntriesWithEmptyTranslationsAreKeptUntranslated(): void
    {
        $binary = self::makeMo([
            ['Solo', ''],
        ], 'V');

        $translations = (new MoLoader())->loadString($binary);
        $translation = $translations->find(null, 'Solo');

        $this->assertNotNull($translation);
        $this->assertNull($translation->translation);
    }

    public function testPluralEntriesAreSplitOnNulBytes(): void
    {
        $binary = self::makeMo([
            ["One\x00Many", "UNO\x00DUE"],
        ], 'V');

        $translations = (new MoLoader())->loadString($binary);
        $translation = $translations->find(null, 'One');

        $this->assertNotNull($translation);
        $this->assertSame('Many', $translation->plural);
        $this->assertSame('UNO', $translation->translation);
        $this->assertSame(['DUE'], $translation->getPluralTranslations());
    }

    public function testInvalidMagicThrows(): void
    {
        $binary = pack('V', 0x12345678) . str_repeat("\0", 32);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not MO file');

        (new MoLoader())->loadString($binary);
    }

    public function testEntryOffsetsBeyondTheFileAreClamped(): void
    {
        // Valid header claiming one entry whose table offsets point past EOF.
        $binary = pack('V', 0x950412DE)
            . pack('V', 0)
            . pack('V', 1)
            . pack('V', 20)
            . pack('V', 28)
            . pack('V', 3) . pack('V', 1 << 20)
            . pack('V', 2) . pack('V', 1 << 20);

        $translations = (new MoLoader())->loadString($binary);

        $this->assertCount(0, $translations);
    }

    /**
     * Builds a minimal in-memory MO file.
     *
     * @param list<array{string, string}> $pairs Original/translation pairs.
     * @param string $endian pack() code: 'V' for little-endian, 'N' for big-endian.
     */
    private static function makeMo(array $pairs, string $endian): string
    {
        $count = count($pairs);
        $originalsTableOffset = 20;
        $translationsTableOffset = $originalsTableOffset + $count * 8;
        $dataOffset = $translationsTableOffset + $count * 8;

        $originalsTable = '';
        $translationsTable = '';
        $data = '';

        foreach ($pairs as [$original, $translation]) {
            $originalsTable .= pack($endian, strlen($original)) . pack($endian, $dataOffset + strlen($data));
            $data .= $original;
            $translationsTable .= pack($endian, strlen($translation)) . pack($endian, $dataOffset + strlen($data));
            $data .= $translation;
        }

        return pack($endian, 0x950412DE)
            . pack($endian, 0)
            . pack($endian, $count)
            . pack($endian, $originalsTableOffset)
            . pack($endian, $translationsTableOffset)
            . $originalsTable
            . $translationsTable
            . $data;
    }
}
