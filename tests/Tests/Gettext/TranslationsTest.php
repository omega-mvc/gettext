<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use InvalidArgumentException;
use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\Languages\CldrData;
use Omega\Gettext\Languages\FormulaConverter;
use Omega\Gettext\Languages\Language;
use Omega\Gettext\Languages\Category;
use Omega\Gettext\Merge;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;

covers(CldrData::class);
covers(Comments::class);
covers(Flags::class);
covers(FormulaConverter::class);
covers(Headers::class);
covers(Category::class);
covers(Language::class);
covers(Merge::class);
covers(References::class);
covers(Translation::class);
covers(Translations::class);

it('creates translations with a domain and a language', function (): void {
    $translations = Translations::create('my-domain', 'it');

    expect($translations->getDomain())->toBe('my-domain');
    expect($translations->getLanguage())->toBe('it');
});

it('rejects unknown languages', function (): void {
    $translations = Translations::create('my-domain');

    expect(fn () => $translations->setLanguage('not-a-language'))
        ->toThrow(InvalidArgumentException::class, 'The language "not-a-language" is not valid');
});

it('lets the headers theirs strategy replace headers', function (): void {
    $ours = Translations::create('domain');
    $ours->getHeaders()->set('X-Ours', 'ours-value');

    $theirs = Translations::create('domain');
    $theirs->getHeaders()->set('X-Theirs', 'theirs-value');
    $theirs->getHeaders()->set(Headers::HEADER_LANGUAGE, 'fr');

    $merged = $ours->mergeWith($theirs, Merge::HEADERS_THEIRS);

    expect($merged->getHeaders()->get('X-Ours'))->toBeNull();
    expect($merged->getHeaders()->get('X-Theirs'))->toBe('theirs-value');
    expect($merged->getHeaders()->get(Headers::HEADER_LANGUAGE))->toBe('fr');
});

it('keeps only the shared entries with the translations theirs strategy', function (): void {
    $ours = Translations::create('domain');
    $ours->add(Translation::create(null, 'shared'));
    $ours->add(Translation::create(null, 'only-ours'));

    $theirs = Translations::create('domain');
    $theirs->add(Translation::create(null, 'shared'));
    $theirs->add(Translation::create(null, 'only-theirs'));

    $merged = $ours->mergeWith($theirs, Merge::TRANSLATIONS_THEIRS);

    expect($merged->find(null, 'shared'))->not->toBeNull();
    expect($merged->find(null, 'only-ours'))->toBeNull();
    expect($merged->find(null, 'only-theirs'))->not->toBeNull();

    $mergedOurs = $ours->mergeWith($theirs, Merge::TRANSLATIONS_OURS);

    expect($mergedOurs->find(null, 'shared'))->not->toBeNull();
    expect($mergedOurs->find(null, 'only-ours'))->not->toBeNull();
    expect($mergedOurs->find(null, 'only-theirs'))->toBeNull();
});
