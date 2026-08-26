<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Generator;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Generator\JsonGenerator;
use Omega\Gettext\Headers;
use Omega\Gettext\Languages\Category;
use Omega\Gettext\Languages\CldrData;
use Omega\Gettext\Languages\FormulaConverter;
use Omega\Gettext\Languages\Language;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(Category::class)]
#[CoversClass(CldrData::class)]
#[CoversClass(FormulaConverter::class)]
#[CoversClass(Language::class)]
#[CoversClass(JsonGenerator::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
class JsonGeneratorTest extends TestCase
{
    public function testJsonGenerator(): void
    {
        $translations = Translations::create('testingdomain');
        $translations->setLanguage('ru');

        $translation = Translation::create(
            null,
            'Ensure this value has at least %(limit_value)d character (it has %sd).'
        );
        $translations->add($translation);

        $translation = Translation::create(null, '%ss must be unique for %ss %ss.');
        $translation->translation = '%ss mora da bude jedinstven za %ss %ss.';
        $translations->add($translation);

        $translation = Translation::create('other-context', '日本人は日本で話される言語です！');
        $translation->translation = 'singular';
        $translation->translatePlural('plural1', 'plural2', 'plural3');
        $translations->add($translation);

        $generator = new JsonGenerator();
        $generator->jsonOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $json = $generator->generateString($translations);
        $expected = file_get_contents(__DIR__ . '/../assets/translations.json');

        $this->assertSame($expected, $json);
    }
}
