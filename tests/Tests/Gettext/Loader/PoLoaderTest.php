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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Tests\TestCase;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
#[CoversClass(Loader::class)]
#[CoversClass(PoLoader::class)]
final class PoLoaderTest extends TestCase
{
    /**
     * @return array<int, array{string, string}>
     */
    public static function stringDecode(): array
    {
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
    }

    public function testLoadsACompletePoFile(): void
    {
        PoLoaderBehaviors::assertPoFile(new PoLoader());
    }

    #[DataProvider('stringDecode')]
    public function testDecodesEscapedStrings(string $source, string $decoded): void
    {
        $this->assertSame($decoded, PoLoader::decode($source));
    }

    public function testHandlesMultilineDisabledTranslations(): void
    {
        PoLoaderBehaviors::assertMultilineDisabled(new PoLoader());
    }
}
