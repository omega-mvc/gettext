<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Generator;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Generator\MoGenerator;
use Omega\Gettext\Headers;
use Omega\Gettext\Languages\Category;
use Omega\Gettext\Languages\CldrData;
use Omega\Gettext\Languages\FormulaConverter;
use Omega\Gettext\Languages\Language;
use Omega\Gettext\Loader\MoLoader;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;

covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(Category::class);
covers(CldrData::class);
covers(FormulaConverter::class);
covers(Language::class);
covers(References::class);
covers(MoGenerator::class);
covers(MoLoader::class);
covers(Translation::class);
covers(Translations::class);

it('generates a mo file and round-trips it', function (): void {
    $generator = new MoGenerator()->includeHeaders();
    $loader = new MoLoader();

    $translations = Translations::create('my-domain');
    $translations->setLanguage('gl_ES');
    $translations->getHeaders()
        ->set('Content-Type', 'text/plain; charset=UTF-8')
        ->set('X-Generator', 'PHP-Gettext');

    $translation = Translation::create('context-1', 'Original');
    $translation->translation = 'Orixinal';
    $translations->add($translation);

    $translation = Translation::create('context-1', 'Other comment');
    $translation->translation = 'Outro comentario';
    $translation->translatePlural('Outros comentarios');
    $translations->add($translation);

    $translation = Translation::create(null, 'Disabled comment');
    $translation->disabled = true;
    $translation->translation = 'Comentario deshabilitado';
    $translations->add($translation);

    $translation = Translation::create(null, '15');
    $translation->translation = '15';
    $translations->add($translation);

    $translation = Translation::create(null, '123456');
    $translation->translation = '12345';
    $translations->add($translation);

    $mo = $generator->generateString($translations);
    $expected = file_get_contents(__DIR__ . '/../assets/mo-generator-result.mo');

    expect($mo)->toBe($expected);

    $result = $loader->loadString($mo);

    expect($result)->toHaveCount(4);
    expect($result->getHeaders())->toHaveCount(5);
});
