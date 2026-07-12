<?php

declare(strict_types=1);

namespace Tests\Gettext\Generator;

use Gettext\Comments;
use Gettext\Flags;
use Gettext\Generator\MoGenerator;
use Gettext\Headers;
use Gettext\Languages\Category;
use Gettext\Languages\CldrData;
use Gettext\Languages\FormulaConverter;
use Gettext\Languages\Language;
use Gettext\Loader\MoLoader;
use Gettext\References;
use Gettext\Translation;
use Gettext\Translations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(Category::class)]
#[CoversClass(CldrData::class)]
#[CoversClass(FormulaConverter::class)]
#[CoversClass(Language::class)]
#[CoversClass(References::class)]
#[CoversClass(MoGenerator::class)]
#[CoversClass(MoLoader::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
class MoGeneratorTest extends TestCase
{
    public function testMoGenerator(): void
    {
        $generator = new MoGenerator()->includeHeaders();
        $loader = new MoLoader();

        $translations = Translations::create('my-domain');
        $translations->setLanguage('gl_ES');
        $translations->getHeaders()
            ->set('Content-Type', 'text/plain; charset=UTF-8')
            ->set('X-Generator', 'PHP-Gettext');

        $translation = Translation::create('context-1', 'Original');
        $translation->translate('Orixinal');
        $translations->add($translation);

        $translation = Translation::create('context-1', 'Other comment');
        $translation->translate('Outro comentario');
        $translation->translatePlural('Outros comentarios');
        $translations->add($translation);

        $translation = Translation::create(null, 'Disabled comment');
        $translation->disable();
        $translation->translate('Comentario deshabilitado');
        $translations->add($translation);

        $translation = Translation::create(null, '15');
        $translation->translate('15');
        $translations->add($translation);

        $translation = Translation::create(null, '123456');
        $translation->translate('12345');
        $translations->add($translation);

        $mo = $generator->generateString($translations);
        $expected = file_get_contents(__DIR__.'/../assets/mo-generator-result.mo');

        $this->assertSame($expected, $mo);

        $result = $loader->loadString($mo);

        $this->assertCount(4, $result);
        $this->assertCount(5, $result->getHeaders());
    }
}
