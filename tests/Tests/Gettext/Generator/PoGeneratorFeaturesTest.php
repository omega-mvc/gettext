<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Generator;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Generator\PoGenerator;
use Omega\Gettext\Headers;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;

covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(PoGenerator::class);
covers(References::class);
covers(Translation::class);
covers(Translations::class);

it('renders references without line numbers as bare filenames', function (): void {
    $translations = Translations::create('po');
    $translation = Translation::create(null, 'Hello');
    $translation->translation = 'Ciao';
    $translation->getReferences()->add('bare-only.php');
    $translations->add($translation);

    $output = (new PoGenerator())->generateString($translations);

    expect($output)->toContain('#: bare-only.php');
    expect($output)->not->toContain('bare-only.php:');
});

it('renders previous strings as old references', function (): void {
    $translations = Translations::create('po');
    $translation = Translation::create('new-context', 'new-original', 'new-plural');
    $translation->translation = 't';
    $translation->previousContext = 'old-context';
    $translation->previousOriginal = 'old-original';
    $translation->previousPlural = 'old-plural';
    $translations->add($translation);

    $output = (new PoGenerator())->generateString($translations);

    expect($output)->toContain('#| msgctxt "old-context"');
    expect($output)->toContain('#| msgid "old-original"');
    expect($output)->toContain('#| msgid_plural "old-plural"');
    expect($output)->toContain('msgctxt "new-context"');
});

it('renders plural entries with indexed msgstr lines', function (): void {
    $translations = Translations::create('po');
    $translations->getHeaders()->set(Headers::HEADER_PLURAL, 'nplurals=3; plural=(n==1 ? 0 : 1);');

    $translation = Translation::create(null, 'One file', '%d files');
    $translation->translation = 'Un file';
    $translation->translatePlural('%d file', '%d file');
    $translations->add($translation);

    $output = (new PoGenerator())->generateString($translations);

    expect($output)->toContain('msgid "One file"');
    expect($output)->toContain('msgid_plural "%d files"');
    expect($output)->toContain('msgstr[0] "Un file"');
    expect($output)->toContain('msgstr[1] "%d file"');
    expect($output)->toContain('msgstr[2] "%d file"');
});
