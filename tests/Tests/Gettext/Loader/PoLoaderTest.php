<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Loader;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\Loader\Loader;
use Omega\Gettext\Loader\PoLoader;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;

covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(References::class);
covers(Translation::class);
covers(Translations::class);
covers(Loader::class);
covers(PoLoader::class);

dataset('stringDecode', function (): array {
    return [
        ['"test"', 'test'],
        ['"\'test\'"', "'test'"],
        ['"Special chars: \\n \\t \\\\ "', "Special chars: \n \t \\ "],
        ['"Newline\nSlash and n\\\\nend"', "Newline\nSlash and n\\nend"],
        ['"Quoted \\"string\\" with %s"', 'Quoted "string" with %s'],
        ['"\\\\x07 - aka \\\\a: \\a"', "\\x07 - aka \\a: \x07"],
        ['"\\\\x08 - aka \\\\b: \\b"', "\\x08 - aka \\b: \x08"],
        ['"\\\\x09 - aka \\\\t: \\t"', "\\x09 - aka \\t: \t"],
        ['"\\\\x0a - aka \\\\n: \\n "', "\\x0a - aka \\n: \n "],
        ['"\\\\x0b - aka \\\\v: \\v"', "\\x0b - aka \\v: \x0b"],
        ['"\\\\x0c - aka \\\\f: \\f"', "\\x0c - aka \\f: \x0c"],
        ['"\\\\x0d - aka \\\\r: \\r "', "\\x0d - aka \\r: \r "],
        ['"\\\\x22 - aka \\": \\""', '\x22 - aka ": "'],
        ['"\\\\x5c - aka \\\\: \\\\"', '\\x5c - aka \\: \\'],
    ];
});

it('loads a complete po file', function (): void {
    PoLoaderBehaviors::assertPoFile(new PoLoader());
});

it('decodes escaped strings', function (string $source, string $decoded): void {
    expect(PoLoader::decode($source))->toBe($decoded);
})->with('stringDecode');

it('handles multiline disabled translations', function (): void {
    PoLoaderBehaviors::assertMultilineDisabled(new PoLoader());
});
