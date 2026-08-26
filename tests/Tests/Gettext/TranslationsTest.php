<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use InvalidArgumentException;
use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\Languages\CldrData;
use Omega\Gettext\Languages\FormulaConverter;
use Omega\Gettext\Languages\Language;
use Omega\Gettext\Languages\Category;
use Omega\Gettext\Merge;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CldrData::class)]
#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(FormulaConverter::class)]
#[CoversClass(Headers::class)]
#[CoversClass(Category::class)]
#[CoversClass(Language::class)]
#[CoversClass(Merge::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
class TranslationsTest extends TestCase
{
    public function testCreateAcceptsDomainAndLanguage(): void
    {
        $translations = Translations::create('my-domain', 'it');

        $this->assertSame('my-domain', $translations->getDomain());
        $this->assertSame('it', $translations->getLanguage());
    }

    public function testSetLanguageRejectsUnknownLanguages(): void
    {
        $translations = Translations::create('my-domain');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The language "not-a-language" is not valid');

        $translations->setLanguage('not-a-language');
    }

    public function testMergeWithHeadersTheirStrategy(): void
    {
        $ours = Translations::create('domain');
        $ours->getHeaders()->set('X-Ours', 'ours-value');

        $theirs = Translations::create('domain');
        $theirs->getHeaders()->set('X-Theirs', 'theirs-value');
        $theirs->getHeaders()->set(Headers::HEADER_LANGUAGE, 'fr');

        $merged = $ours->mergeWith($theirs, Merge::HEADERS_THEIRS);

        $this->assertNull($merged->getHeaders()->get('X-Ours'));
        $this->assertSame('theirs-value', $merged->getHeaders()->get('X-Theirs'));
        $this->assertSame('fr', $merged->getHeaders()->get(Headers::HEADER_LANGUAGE));
    }

    public function testMergeWithTranslationsTheirStrategyKeepsOnlySharedEntries(): void
    {
        $ours = Translations::create('domain');
        $ours->add(Translation::create(null, 'shared'));
        $ours->add(Translation::create(null, 'only-ours'));

        $theirs = Translations::create('domain');
        $theirs->add(Translation::create(null, 'shared'));
        $theirs->add(Translation::create(null, 'only-theirs'));

        $merged = $ours->mergeWith($theirs, Merge::TRANSLATIONS_THEIRS);

        $this->assertNotNull($merged->find(null, 'shared'));
        $this->assertNull($merged->find(null, 'only-ours'));
        $this->assertNotNull($merged->find(null, 'only-theirs'));

        $mergedOurs = $ours->mergeWith($theirs, Merge::TRANSLATIONS_OURS);

        $this->assertNotNull($mergedOurs->find(null, 'shared'));
        $this->assertNotNull($mergedOurs->find(null, 'only-ours'));
        $this->assertNull($mergedOurs->find(null, 'only-theirs'));
    }
}
