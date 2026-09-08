<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Loader;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\Loader\JsonLoader;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;

covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(References::class);
covers(Translation::class);
covers(Translations::class);
covers(JsonLoader::class);

it('loads translations from a json file', function (): void {
    $loader = new JsonLoader();

    $translations = $loader->loadFile(__DIR__ . '/../assets/translations.json');

    expect($translations)->toHaveCount(2);
    expect($translations->getDomain())->toBe('testingdomain');

    $translation = $translations->find(null, '%ss must be unique for %ss %ss.');

    $this->assertNotNull($translation);
    expect($translation->translation)->toBe('%ss mora da bude jedinstven za %ss %ss.');
    expect($translation->getPluralTranslations())->toHaveCount(0);

    $translation = $translations->find('other-context', '日本人は日本で話される言語です！');

    $this->assertNotNull($translation);
    expect($translation->translation)->toBe('singular');
    expect($translation->getPluralTranslations())->toHaveCount(2);
    expect($translation->getPluralTranslations())->toBe(['plural1', 'plural2']);
});
