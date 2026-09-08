<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Generator;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Generator\PoGenerator;
use Omega\Gettext\Headers;
use Omega\Gettext\Languages\Category;
use Omega\Gettext\Languages\CldrData;
use Omega\Gettext\Languages\FormulaConverter;
use Omega\Gettext\Languages\Language;
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
covers(PoGenerator::class);
covers(Translation::class);
covers(Translations::class);

it('generates a complete po file', function (): void {
    $generator = new PoGenerator();
    $translations = Translations::create('my-domain');
    $translations->getFlags()->add('fuzzy');
    $translations->description =
        <<<'EOT'
SOME DESCRIPTIVE TITLE
Copyright (C) YEAR Free Software Foundation, Inc.
This file is distributed under the same license as the PACKAGE package.
FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
EOT
    ;
    $translations->setLanguage('gl_ES');
    $translations->getHeaders()
        ->set('Content-Type', 'text/plain; charset=UTF-8')
        ->set('X-Generator', 'PHP-Gettext');

    $translation = Translation::create('context-1', 'Original');
    $translation->getComments()->add('This is a comment');
    $translation->getReferences()->add('/my/template.php', 45);
    $translations->add($translation);

    $translation = Translation::create('context-1', 'Other comment');
    $translation->translation = 'Outro comentario';
    $translation->translatePlural('Outros comentarios');
    $translation->getExtractedComments()->add('Not sure about this');
    $translation->getFlags()->add('c-code');
    $translations->add($translation);

    $translation = Translation::create(null, 'Disabled comment');
    $translation->disabled = true;
    $translation->translation = 'Comentario deshabilitado';
    $translation->getComments()->add('This is a disabled comment');
    $translations->add($translation);

    // https://github.com/php-gettext/Gettext/issues/244
    $translation = Translation::create(null, "foo\nbar");
    $translation->translation = "bar\nbaz";
    $translations->add($translation);

    $result = $generator->generateString($translations);

    $expected = <<<'EOT'
# SOME DESCRIPTIVE TITLE
# Copyright (C) YEAR Free Software Foundation, Inc.
# This file is distributed under the same license as the PACKAGE package.
# FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
#
#, fuzzy
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Language: gl_ES\n"
"Plural-Forms: nplurals=2; plural=n != 1;\n"
"X-Domain: my-domain\n"
"X-Generator: PHP-Gettext\n"

# This is a comment
#: /my/template.php:45
msgctxt "context-1"
msgid "Original"
msgstr ""

#. Not sure about this
#, c-code
msgctxt "context-1"
msgid "Other comment"
msgstr "Outro comentario"

# This is a disabled comment
#~ msgid "Disabled comment"
#~ msgstr "Comentario deshabilitado"

msgid ""
"foo\n"
"bar"
msgstr ""
"bar\n"
"baz"

EOT;

    expect($result)->toBe($expected);
});

dataset('stringEncode', function (): array {
    return [
        ['"test"', 'test'],
        ['"\'test\'"', "'test'"],
        ['"Special chars: \\n \\t \\\\ "', "Special chars: \n \t \\ "],
        ['"Newline\nSlash and n\\\\nend"', "Newline\nSlash and n\\nend"],
        ['"Quoted \\"string\\" with %s"', 'Quoted "string" with %s'],
    ];
});

it('encodes strings for po files', function (string $encoded, string $decoded): void {
    expect(PoGenerator::encode($decoded))->toBe($encoded);
})->with('stringEncode');
