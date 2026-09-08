<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Loader;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\Loader\Loader;
use Omega\Gettext\Loader\StrictPoLoader;
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
covers(StrictPoLoader::class);

dataset('stringDecodeStrict', function (): array {
    return array_merge(
        [
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
        ],
        [
            ['"Up to 3 digits, 1 will be skipped \\0\\00\\0001"', "Up to 3 digits, 1 will be skipped \0\0\0001"],
            ['"\\101\\102"', 'AB'],
            ['"Works with a single character \\x41\\xA\\xD\\x5A\\x5a"', "Works with a single character A\n\rZZ"],
            ['"Last hex pair: tab = \\x12345678AAAAaaaa09"', "Last hex pair: tab = \t"],
            ['"UTF-8, up to 4 digits \u00c0A\u00C1 \u0C0\u41\uC1"', 'UTF-8, up to 4 digits ÀAÁ ÀAÁ'],
            ['"UTF-32, up to 8 digits \U000000c0A\U00C1 \U0C0\U41\UC1"', 'UTF-32, up to 8 digits ÀAÁ ÀAÁ'],
        ]
    );
});

dataset('badFormattedPo', function (): array {
    return [
        'Duplicated entry' => ['/Duplicated entry/', <<<'EOT'
msgid"original"
msgstr"translation"

msgid"original"
msgstr"translation 2"
EOT],
        'Out of order: msgstr before msgid' => ['/Expected msgid/', <<<'EOT'
msgstr "translation"
msgid "original"
EOT],
        'Out of order: msgctxt before msgid' => ['/Expected msgid/', <<<'EOT'
msgctxt "ctx"
msgstr "translation"
msgid "original"
EOT],
        'Out of order: Comment between the definitions' => ['/Expected msgstr/', <<<'EOT'
msgid "original"
# Unexpected comment
msgstr "translation"
EOT],
        'Out of order: Disabled translations (#~) cannot appear after previous translations (#|)' => [
            '/Inconsistent use of #~/',
            <<<'EOT'
#|msgid "previous"
#~msgid "disabled"
#~msgstr "disabled translation"
msgid "original"
msgstr "translation"
EOT,
        ],
        'Out of order: msgctxt of a previous translation (#|) must appear before its msgid' => [
            '/Cannot redeclare the previous comment/',
            <<<'EOT'
#|msgid "previous"
#|msgctxt "previous context"
#|msgid_plural "previous context"
msgid "original"
msgstr "translation"
EOT,
        ],
        'Indexed msgstr: msgid_plural requires an indexed msgstr' => ['/Expected character "\\["/', <<<'EOT'
msgid "original"
msgid_plural "plural"
msgstr "translation"
EOT],
        'Indexed msgstr: After the index 0, the next should be 2' => ['/The msgstr has an invalid index/', <<<'EOT'
msgid "original"
msgid_plural "plural"
msgstr[0] "translation"
msgstr[2] "translation"
EOT],
        'Indexed msgstr: Index has trash data (whitespace is ok)' => ['/Expected character "]"/', <<<'EOT'
msgid "original"
msgid_plural "plural"
msgstr[   0   ] "translation"
msgstr[1s] "translation"
EOT],
        'Incomplete translation' => ['/Expected msgstr/', <<<'EOT'
msgid "original"
EOT],
        'Incomplete disabled translation' => ['/Expected msgstr/', <<<'EOT'
#~ msgid "original"
EOT],
        'Encoding: No quotes' => ['/Expected an opening quote/', <<<'EOT'
msgid "original"
msgstr translation
EOT],
        'Encoding: Missing opening quote' => ['/Expected an opening quote/', <<<'EOT'
msgid "original"
msgstr translation"
EOT],
        'Encoding: Missing closing quote' => ['/Expected a closing quot/', <<<'EOT'
msgid "original"
msgstr "translation
EOT],
        'Encoding: Unescaped newline (using \\n)' => ['/Newline character must be escaped/', <<<EOT
msgid "original"
msgstr "trans\nlation"
EOT],
        'Encoding: Unescaped newline (using \\r)' => ['/Newline character must be escaped/', <<<EOT
msgid "original"
msgstr "trans\rlation"
EOT],
        'Encoding: Invalid octal digit' => ['/Invalid escaped character/', <<<'EOT'
msgid "original"
msgstr "translation\8"
EOT],
        'Encoding: Octal out of range' => ['/Octal value out of range/', <<<'EOT'
msgid "original"
msgstr "translation\777"
EOT],
        'Encoding: Invalid hexadecimal digit' => ['/Expected at least 1 occurrence of hexadecimal/', <<<'EOT'
msgid "original"
msgstr "translation\xGG"
EOT],
        'Encoding: Invalid unicode digit' => ['/Expected at least 1 occurrence of hexadecimal/', <<<'EOT'
msgid "original"
msgstr "translation\uZZ"
EOT],
        'Invalid identifier "unknown"' => ['/Expected msgid/', <<<'EOT'
unknown "original"
msgstr "translation"
EOT],
        'msgid, msgid_plural and msgstr cannot begin nor end with a newline' => [
            '/msgstr cannot start nor end with a newline/',
            <<<'EOT'
msgid "original"
msgstr "translation\n"
EOT,
            true,
        ],
        'Missing header' => ['/The loaded string has no header translation/', <<<'EOT'
msgid "original"
msgstr "translation"
EOT, true],
        'Duplicated header' => ['/Header already defined/', <<<'EOT'
msgid ""
msgstr "Header: \n"
"Header: \n"
EOT, true],
        'Malformed header name' => ['/Malformed header name/', <<<'EOT'
msgid ""
msgstr "Header\n"
EOT, true],
        'Missing standard headers Language/Plural-Forms/Content-Type' => ['/header not declared or empty/', <<<'EOT'
msgid ""
msgstr "Header: Value\n"
EOT, true],
        'Two plural forms with just one plural translation' => [
            '/The translation has \\d+ plural forms, while the header expects \\d+/',
            <<<'EOT'
msgid ""
msgstr "Language: en_US\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=n != 1;\n"

msgid "original"
msgid_plural "plural"
msgstr[0] "translation"
EOT,
            true,
        ],
        'Two plural forms with 3 plural translations' => [
            '/The translation has \\d+ plural forms, while the header expects \\d+/',
            <<<'EOT'
msgid ""
msgstr "Language: en_US\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=n != 1;\n"

msgid "original"
msgid_plural "plural"
msgstr[0] "translation"
msgstr[1] "translation"
msgstr[2] "translation"
EOT,
            true,
        ],
        'Dangling comment in the end of the data' => ['/Comment ignored at the end/', <<<'EOT'
msgid "original"
msgstr "translation"

# Dangling comment
EOT, true],
        'Dangling comment in the end of the data using error report with line/column' => [
            '/Comment ignored at the end.*line 4 column 34/',
            'msgid "original"
                msgstr "translation"
                
                # Dangling comment',
            true,
            true,
        ],
    ];
});

it('loads a complete po file', function (): void {
    PoLoaderBehaviors::assertPoFile(new StrictPoLoader());
});

it('decodes escaped strings', function (string $source, string $decoded): void {
    PoLoaderBehaviors::assertStringDecode(new StrictPoLoader(), $source, $decoded);
})->with('stringDecodeStrict');

it('handles multiline disabled translations', function (): void {
    PoLoaderBehaviors::assertMultilineDisabled(new StrictPoLoader());
});

it('parses collapsed syntax', function (): void {
    $po = "#   comment\nmsgctxt\"ctx\"msgid\"original\"msgstr\"trans\"\"lation\"";
    $translations = (new StrictPoLoader())->loadString($po);
    $translation = $translations->find('ctx', 'original');
    $this->assertNotNull($translation);
    expect($translation->translation)->toBe('translation');
    expect($translation->getComments()->toArray()[0])->toBe('  comment');
});

it('carries previous translations', function (): void {
    $po = <<<'EOT'
#| msgctxt "previous ctx"
        #| msgid "previous original"
        #| msgid_plural "previous plural"
        msgctxt "ctx"
        msgid "original"
        msgid_plural "plural"
        msgstr[0] "translation"
EOT;
    $translations = (new StrictPoLoader())->loadString($po);

    $translation = $translations->find('ctx', 'original');
    $this->assertNotNull($translation);
    expect($translation->getContext())->toBe('ctx');
    expect($translation->getOriginal())->toBe('original');
    expect($translation->plural)->toBe('plural');
    expect($translation->translation)->toBe('translation');

    expect($translation->previousContext)->toBe('previous ctx');
    expect($translation->previousOriginal)->toBe('previous original');
    expect($translation->previousPlural)->toBe('previous plural');
});

it('parses disabled entries with previous translations', function (): void {
    $po = <<<'EOT'
#~ #| msgctxt "previous ctx"
        #~ #| msgid "previous original"
        #~ #| msgid_plural "previous plural"
        #~ msgctxt "ctx"
        #~ msgid "original"
        #~ msgid_plural "plural"
        #~ msgstr[0] "translation"
EOT;
    $translations = (new StrictPoLoader())->loadString($po);

    $translation = $translations->find('ctx', 'original');
    $this->assertNotNull($translation);
    expect($translation->disabled)->toBeTrue();
    expect($translation->getContext())->toBe('ctx');
    expect($translation->getOriginal())->toBe('original');
    expect($translation->plural)->toBe('plural');
    expect($translation->translation)->toBe('translation');

    expect($translation->previousContext)->toBe('previous ctx');
    expect($translation->previousOriginal)->toBe('previous original');
    expect($translation->previousPlural)->toBe('previous plural');
});

it(
    'rejects malformed po content',
    function (
        string $exceptionPattern,
        string $po,
        bool $throwOnWarning = false,
        bool $displayErrorLine = false
    ): void {
        $this->expectExceptionMessageMatches($exceptionPattern);
        $loader = new StrictPoLoader();
        $loader->throwOnWarning = $throwOnWarning;
        $loader->displayErrorLine = $displayErrorLine;
        $loader->loadString($po);
    }
)->with('badFormattedPo');
