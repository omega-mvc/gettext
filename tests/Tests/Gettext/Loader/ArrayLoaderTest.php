<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Loader;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\Loader\ArrayLoader;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;

covers(Flags::class);
covers(Headers::class);
covers(References::class);
covers(Comments::class);
covers(Translation::class);
covers(Translations::class);
covers(ArrayLoader::class);

it('loads translations from an array file', function (): void {
    $loader = new ArrayLoader();

    $translations = $loader->loadFile(__DIR__ . '/../assets/translations.php');

    expect($translations->getHeaders())->toHaveCount(2);
    expect($translations->getDomain())->toBe('testingdomain');
    expect($translations->getHeaders()->get('Plural-Forms'))->toBe(
        'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);'
    );
    expect($translations)->toHaveCount(10);

    $translation = $translations->find(null, 'Integer');

    $this->assertNotNull($translation);
    expect($translation->translation)->toBe('Cijeo broj');
    expect($translation->getPluralTranslations())->toHaveCount(0);
});
