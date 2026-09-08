<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Loader;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\Loader\PoLoader;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;

covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(PoLoader::class);
covers(References::class);
covers(Translation::class);
covers(Translations::class);

it('resolves contextual entries', function (): void {
    $po = 'msgctxt "menu"' . "\n"
        . 'msgid "File"' . "\n"
        . 'msgstr "Archivo"' . "\n";

    $translations = (new PoLoader())->loadString($po);
    $translation = $translations->find('menu', 'File');

    $this->assertNotNull($translation);
    expect($translation->translation)->toBe('Archivo');
});

it('skips unknown keywords', function (): void {
    $po = 'msgid "kept"' . "\n"
        . 'msgstr "value"' . "\n"
        . 'unknownkeyword "whatever"' . "\n";

    $translations = (new PoLoader())->loadString($po);

    expect($translations->find(null, 'kept'))->not->toBeNull();
});

it('parses files without a header block', function (): void {
    $po = 'msgid "Hello"' . "\n"
        . 'msgstr "Ciao"' . "\n";

    $translations = (new PoLoader())->loadString($po);

    expect($translations->getHeaders()->toArray())->toBe([]);
    expect($translations->find(null, 'Hello'))->not->toBeNull();
});

it('parses empty header blocks as no headers', function (): void {
    $po = 'msgid ""' . "\n"
        . 'msgstr ""' . "\n"
        . "\n"
        . 'msgid "Hello"' . "\n"
        . 'msgstr "Ciao"' . "\n";

    $translations = (new PoLoader())->loadString($po);

    expect($translations->getHeaders()->toArray())->toBe([]);
    expect($translations->find(null, 'Hello'))->not->toBeNull();
});

it('concatenates multiline header values', function (): void {
    $po = 'msgid ""' . "\n"
        . 'msgstr ""' . "\n"
        . '"Project-Id-Version: wrapped\ncontinued without colon\n"' . "\n"
        . '"Language: it\n"' . "\n";

    $translations = (new PoLoader())->loadString($po);

    expect($translations->getHeaders()->get('Project-Id-Version'))
        ->toBe('wrappedcontinued without colon');
});
