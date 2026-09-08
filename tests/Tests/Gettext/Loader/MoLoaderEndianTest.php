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

covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(MoLoader::class);
covers(References::class);
covers(Translation::class);
covers(Translations::class);

it('loads big endian files', function (): void {
    $binary = makeMo([
        ['', "Language: it\n\nContent-Type: text/plain\n"],
        ['Hello', 'Ciao'],
    ], 'N');

    $translations = (new MoLoader())->loadString($binary);

    expect($translations->getHeaders()->get('Language'))->toBe('it');
    $translation = $translations->find(null, 'Hello');

    $this->assertNotNull($translation);
    expect($translation->translation)->toBe('Ciao');
});

it('keeps entries with empty translations untranslated', function (): void {
    $binary = makeMo([
        ['Solo', ''],
    ], 'V');

    $translations = (new MoLoader())->loadString($binary);
    $translation = $translations->find(null, 'Solo');

    $this->assertNotNull($translation);
    expect($translation->translation)->toBeNull();
});

it('splits plural entries on nul bytes', function (): void {
    $binary = makeMo([
        ["One\x00Many", "UNO\x00DUE"],
    ], 'V');

    $translations = (new MoLoader())->loadString($binary);
    $translation = $translations->find(null, 'One');

    $this->assertNotNull($translation);
    expect($translation->plural)->toBe('Many');
    expect($translation->translation)->toBe('UNO');
    expect($translation->getPluralTranslations())->toBe(['DUE']);
});

it('rejects an invalid magic number', function (): void {
    $binary = pack('V', 0x12345678) . str_repeat("\0", 32);

    expect(fn () => (new MoLoader())->loadString($binary))
        ->toThrow(Exception::class, 'Not MO file');
});

it('clamps entry offsets beyond the file', function (): void {
    // Valid header claiming one entry whose table offsets point past EOF.
    $binary = pack('V', 0x950412DE)
        . pack('V', 0)
        . pack('V', 1)
        . pack('V', 20)
        . pack('V', 28)
        . pack('V', 3) . pack('V', 1 << 20)
        . pack('V', 2) . pack('V', 1 << 20);

    $translations = (new MoLoader())->loadString($binary);

    expect($translations)->toHaveCount(0);
});

/**
 * Builds a minimal in-memory MO file.
 *
 * @param list<array{string, string}> $pairs Original/translation pairs.
 * @param string $endian pack() code: 'V' for little-endian, 'N' for big-endian.
 */
function makeMo(array $pairs, string $endian): string
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
