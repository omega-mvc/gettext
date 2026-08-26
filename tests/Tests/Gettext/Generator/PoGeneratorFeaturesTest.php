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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(PoGenerator::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
class PoGeneratorFeaturesTest extends TestCase
{
    public function testReferencesWithoutLineNumbersRenderBareFilenames(): void
    {
        $translations = Translations::create('po');
        $translation = Translation::create(null, 'Hello');
        $translation->translate('Ciao');
        $translation->getReferences()->add('bare-only.php');
        $translations->add($translation);

        $output = (new PoGenerator())->generateString($translations);

        $this->assertStringContainsString('#: bare-only.php', $output);
        $this->assertStringNotContainsString('bare-only.php:', $output);
    }

    public function testPreviousStringsAreRenderedAsOldReferences(): void
    {
        $translations = Translations::create('po');
        $translation = Translation::create('new-context', 'new-original', 'new-plural');
        $translation->translate('t');
        $translation->setPreviousContext('old-context');
        $translation->setPreviousOriginal('old-original');
        $translation->setPreviousPlural('old-plural');
        $translations->add($translation);

        $output = (new PoGenerator())->generateString($translations);

        $this->assertStringContainsString('#| msgctxt "old-context"', $output);
        $this->assertStringContainsString('#| msgid "old-original"', $output);
        $this->assertStringContainsString('#| msgid_plural "old-plural"', $output);
        $this->assertStringContainsString('msgctxt "new-context"', $output);
    }

    public function testPluralEntriesRenderIndexedMsgstrLines(): void
    {
        $translations = Translations::create('po');
        $translations->getHeaders()->set(Headers::HEADER_PLURAL, 'nplurals=3; plural=(n==1 ? 0 : 1);');

        $translation = Translation::create(null, 'One file', '%d files');
        $translation->translate('Un file');
        $translation->translatePlural('%d file', '%d file');
        $translations->add($translation);

        $output = (new PoGenerator())->generateString($translations);

        $this->assertStringContainsString('msgid "One file"', $output);
        $this->assertStringContainsString('msgid_plural "%d files"', $output);
        $this->assertStringContainsString('msgstr[0] "Un file"', $output);
        $this->assertStringContainsString('msgstr[1] "%d file"', $output);
        $this->assertStringContainsString('msgstr[2] "%d file"', $output);
    }
}
