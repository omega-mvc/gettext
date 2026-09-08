<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Generator;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Generator\AbstractGenerator;
use Omega\Gettext\Generator\ArrayGenerator;
use Omega\Gettext\Generator\JsonGenerator;
use Omega\Gettext\Generator\MoGenerator;
use Omega\Gettext\Generator\PoGenerator;
use Omega\Gettext\Headers;
use Omega\Gettext\Loader\MoLoader;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;
use RuntimeException;

covers(AbstractGenerator::class);
covers(ArrayGenerator::class);
covers(JsonGenerator::class);
covers(MoGenerator::class);
covers(MoLoader::class);
covers(PoGenerator::class);
covers(Translation::class);
covers(Translations::class);
covers(Headers::class);
covers(Comments::class);
covers(Flags::class);
covers(References::class);

it('writes generated files to disk', function (): void {
    $translations = Translations::create('filedom');
    $translations->add(translatedEntry());

    $file = sys_get_temp_dir() . '/gettext-generate-' . uniqid() . '.po';

    try {
        $generator = new PoGenerator();

        expect($generator->generateFile($translations, $file))->toBeTrue();
        expect($file)->toBeFile();
        expect((string) file_get_contents($file))->toContain('msgid "Hello"');
    } finally {
        if (is_file($file)) {
            unlink($file);
        }
    }
});

it('renders empty message sections in pretty arrays', function (): void {
    $generator = new ArrayGenerator(['pretty' => true]);
    $output = $generator->generateString(Translations::create('empty'));

    expect($output)->toContain("'messages' => [],");
});

it('rejects non-utf8 payloads when generating json', function (): void {
    $translations = Translations::create('json');
    $translation = Translation::create(null, "bad-\xB1-utf8");
    $translation->translation = 'ok';
    $translations->add($translation);

    $generator = new JsonGenerator();

    expect(fn () => $generator->generateString($translations))
        ->toThrow(RuntimeException::class, 'Cannot encode translations to JSON:');
});

it('round-trips plural entries through the mo generator', function (): void {
    $translations = Translations::create('mo');
    $translations->getHeaders()->set(Headers::HEADER_PLURAL, 'nplurals=2; plural=(n != 1);');

    $pluralEntry = Translation::create(null, 'One apple', '%d apples');
    $pluralEntry->translation = 'Una mela';
    $pluralEntry->translatePlural('%d mele');
    $translations->add($pluralEntry);

    $barePlural = Translation::create(null, 'Solo');
    $barePlural->translation = 'S';
    $barePlural->plural = 'Soloplural';
    $translations->add($barePlural);

    $binary = (new MoGenerator())->generateString($translations);
    $loaded = (new MoLoader())->loadString($binary);

    $apple = $loaded->find(null, 'One apple');

    $this->assertNotNull($apple);
    expect($apple->plural)->toBe('%d apples');
    expect($apple->translation)->toBe('Una mela');
    expect($apple->getPluralTranslations())->toBe(['%d mele']);

    $solo = $loaded->find(null, 'Solo');

    $this->assertNotNull($solo);
    expect($solo->translation)->toBe('S');
});

it('works without a plural-forms header in the mo generator', function (): void {
    $translations = Translations::create('noheader');

    $entry = Translation::create(null, 'One apple', '%d apples');
    $entry->translation = 'Una mela';
    $entry->translatePlural('%d mele');
    $translations->add($entry);

    $binary = (new MoGenerator())->generateString($translations);
    $loaded = (new MoLoader())->loadString($binary);
    $apple = $loaded->find(null, 'One apple');

    $this->assertNotNull($apple);
    expect($apple->getPluralTranslations())->toBe(['%d mele']);
});

function translatedEntry(): Translation
{
    $translation = Translation::create(null, 'Hello');
    $translation->translation = 'Ciao';

    return $translation;
}
