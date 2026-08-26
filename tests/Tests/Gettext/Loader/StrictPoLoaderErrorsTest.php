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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(References::class)]
#[CoversClass(StrictPoLoader::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
class StrictPoLoaderErrorsTest extends TestCase
{
    public function testOctalEscapesAboveSignedCharRangeAreRejected(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Octal value out of range [0, 0177]');

        (new StrictPoLoader())->loadString('msgid "\400"' . "\n" . 'msgstr "v"');
    }

    public function testPluralEntriesWithoutIndexedTranslationThrow(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Expected indexed msgstr');

        (new StrictPoLoader())->loadString('msgid "a"' . "\n" . 'msgid_plural "b"' . "\n" . 'msgid "c"');
    }

    public function testMalformedHeaderNamesProduceWarnings(): void
    {
        $loader = new StrictPoLoader();
        $translations = $loader->loadString(
            'msgid ""' . "\n"
            . 'msgstr ""' . "\n"
            . '"junkline\nLanguage: it\n"'
        );

        $warnings = $loader->getWarnings();

        $this->assertCount(0, $translations);
        $this->assertArrayHasKey(0, $warnings);
        $this->assertStringContainsString('Malformed header name', (string) $warnings[0]);
    }

    public function testExtractedCommentsAreCollected(): void
    {
        $translations = (new StrictPoLoader())->loadString(
            '#. extracted note' . "\n"
            . 'msgid "a"' . "\n"
            . 'msgstr "b"'
        );

        $translation = $translations->find(null, 'a');

        $this->assertNotNull($translation);
        $this->assertSame(['extracted note'], $translation->getExtractedComments()->toArray());
    }

    public function testDuplicatedHeadersProduceWarnings(): void
    {
        $loader = new StrictPoLoader();
        $loader->loadString(
            'msgid ""' . "\n"
            . 'msgstr ""' . "\n"
            . '"Language: it\nLanguage: fr\n"'
        );

        $this->assertStringContainsString('Header already defined', implode("\n", $loader->getWarnings()));
    }
}
