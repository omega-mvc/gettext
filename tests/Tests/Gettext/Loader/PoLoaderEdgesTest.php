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
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Tests\TestCase;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(PoLoader::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
final class PoLoaderEdgesTest extends TestCase
{
    public function testResolvesContextualEntries(): void
    {
        $po = 'msgctxt "menu"' . "\n"
            . 'msgid "File"' . "\n"
            . 'msgstr "Archivo"' . "\n";

        $translations = (new PoLoader())->loadString($po);
        $translation = $translations->find('menu', 'File');

        $this->assertNotNull($translation);
        $this->assertSame('Archivo', $translation->translation);
    }

    public function testSkipsUnknownKeywords(): void
    {
        $po = 'msgid "kept"' . "\n"
            . 'msgstr "value"' . "\n"
            . 'unknownkeyword "whatever"' . "\n";

        $translations = (new PoLoader())->loadString($po);

        $this->assertNotNull($translations->find(null, 'kept'));
    }

    public function testParsesFilesWithoutAHeaderBlock(): void
    {
        $po = 'msgid "Hello"' . "\n"
            . 'msgstr "Ciao"' . "\n";

        $translations = (new PoLoader())->loadString($po);

        $this->assertSame([], $translations->getHeaders()->toArray());
        $this->assertNotNull($translations->find(null, 'Hello'));
    }

    public function testParsesEmptyHeaderBlocksAsNoHeaders(): void
    {
        $po = 'msgid ""' . "\n"
            . 'msgstr ""' . "\n"
            . "\n"
            . 'msgid "Hello"' . "\n"
            . 'msgstr "Ciao"' . "\n";

        $translations = (new PoLoader())->loadString($po);

        $this->assertSame([], $translations->getHeaders()->toArray());
        $this->assertNotNull($translations->find(null, 'Hello'));
    }

    public function testConcatenatesMultilineHeaderValues(): void
    {
        $po = 'msgid ""' . "\n"
            . 'msgstr ""' . "\n"
            . '"Project-Id-Version: wrapped\ncontinued without colon\n"' . "\n"
            . '"Language: it\n"' . "\n";

        $translations = (new PoLoader())->loadString($po);

        $this->assertSame(
            'wrappedcontinued without colon',
            $translations->getHeaders()->get('Project-Id-Version')
        );
    }
}
