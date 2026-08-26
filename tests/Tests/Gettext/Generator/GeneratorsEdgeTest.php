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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(AbstractGenerator::class)]
#[CoversClass(ArrayGenerator::class)]
#[CoversClass(JsonGenerator::class)]
#[CoversClass(MoGenerator::class)]
#[CoversClass(MoLoader::class)]
#[CoversClass(PoGenerator::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
#[CoversClass(Headers::class)]
#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(References::class)]
class GeneratorsEdgeTest extends TestCase
{
    public function testGenerateFileWritesToDisk(): void
    {
        $translations = Translations::create('filedom');
        $translations->add($this->translatedEntry());

        $file = sys_get_temp_dir() . '/gettext-generate-' . uniqid() . '.po';

        try {
            $generator = new PoGenerator();

            $this->assertTrue($generator->generateFile($translations, $file));
            $this->assertFileExists($file);
            $this->assertStringContainsString('msgid "Hello"', (string) file_get_contents($file));
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testPrettyArrayGeneratorRendersEmptyMessageSections(): void
    {
        $generator = new ArrayGenerator(['pretty' => true]);
        $output = $generator->generateString(Translations::create('empty'));

        $this->assertStringContainsString("'messages' => [],", $output);
    }

    public function testJsonGeneratorRejectsNonUtf8Payloads(): void
    {
        $translations = Translations::create('json');
        $translation = Translation::create(null, "bad-\xB1-utf8");
        $translation->translate('ok');
        $translations->add($translation);

        $generator = new JsonGenerator();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot encode translations to JSON:');

        $generator->generateString($translations);
    }

    public function testMoGeneratorRoundTripsPluralEntries(): void
    {
        $translations = Translations::create('mo');
        $translations->getHeaders()->set(Headers::HEADER_PLURAL, 'nplurals=2; plural=(n != 1);');

        $pluralEntry = Translation::create(null, 'One apple', '%d apples');
        $pluralEntry->translate('Una mela');
        $pluralEntry->translatePlural('%d mele');
        $translations->add($pluralEntry);

        $barePlural = Translation::create(null, 'Solo');
        $barePlural->translate('S');
        $barePlural->setPlural('Soloplural');
        $translations->add($barePlural);

        $binary = (new MoGenerator())->generateString($translations);
        $loaded = (new MoLoader())->loadString($binary);

        $apple = $loaded->find(null, 'One apple');

        $this->assertNotNull($apple);
        $this->assertSame('%d apples', $apple->getPlural());
        $this->assertSame('Una mela', $apple->getTranslation());
        $this->assertSame(['%d mele'], $apple->getPluralTranslations());

        $solo = $loaded->find(null, 'Solo');

        $this->assertNotNull($solo);
        $this->assertSame('S', $solo->getTranslation());
    }

    public function testMoGeneratorWorksWithoutPluralFormsHeader(): void
    {
        $translations = Translations::create('noheader');

        $entry = Translation::create(null, 'One apple', '%d apples');
        $entry->translate('Una mela');
        $entry->translatePlural('%d mele');
        $translations->add($entry);

        $binary = (new MoGenerator())->generateString($translations);
        $loaded = (new MoLoader())->loadString($binary);
        $apple = $loaded->find(null, 'One apple');

        $this->assertNotNull($apple);
        $this->assertSame(['%d mele'], $apple->getPluralTranslations());
    }

    private function translatedEntry(): Translation
    {
        $translation = Translation::create(null, 'Hello');
        $translation->translate('Ciao');

        return $translation;
    }
}
