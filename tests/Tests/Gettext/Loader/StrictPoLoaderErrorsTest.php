<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Loader;

use Exception;
use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\Loader\StrictPoLoader;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;

covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(References::class);
covers(StrictPoLoader::class);
covers(Translation::class);
covers(Translations::class);

it('rejects octal escapes above the signed char range', function (): void {
    expect(fn () => (new StrictPoLoader())->loadString('msgid "\400"' . "\n" . 'msgstr "v"'))
        ->toThrow(Exception::class, 'Octal value out of range [0, 0177]');
});

it('throws for plural entries without an indexed translation', function (): void {
    expect(fn () => (new StrictPoLoader())->loadString('msgid "a"' . "\n" . 'msgid_plural "b"' . "\n" . 'msgid "c"'))
        ->toThrow(Exception::class, 'Expected indexed msgstr');
});

it('produces warnings for malformed header names', function (): void {
    $loader = new StrictPoLoader();
    $translations = $loader->loadString(
        'msgid ""' . "\n"
        . 'msgstr ""' . "\n"
        . '"junkline\nLanguage: it\n"'
    );

    $warnings = $loader->getWarnings();

    expect($translations)->toHaveCount(0);
    expect($warnings)->not->toBeEmpty();
    expect((string) $warnings[0])->toContain('Malformed header name');
});

it('collects extracted comments', function (): void {
    $translations = (new StrictPoLoader())->loadString(
        '#. extracted note' . "\n"
        . 'msgid "a"' . "\n"
        . 'msgstr "b"'
    );

    $translation = $translations->find(null, 'a');

    $this->assertNotNull($translation);
    expect($translation->getExtractedComments()->toArray())->toBe(['extracted note']);
});

it('produces warnings for duplicated headers', function (): void {
    $loader = new StrictPoLoader();
    $loader->loadString(
        'msgid ""' . "\n"
        . 'msgstr ""' . "\n"
        . '"Language: it\nLanguage: fr\n"'
    );

    expect(implode("\n", $loader->getWarnings()))->toContain('Header already defined');
});
