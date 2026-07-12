<?php

declare(strict_types=1);

namespace Tests\Gettext\Loader;

use Gettext\Comments;
use Gettext\Flags;
use Gettext\Headers;
use Gettext\Loader\JsonLoader;
use Gettext\References;
use Gettext\Translation;
use Gettext\Translations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
#[CoversClass(JsonLoader::class)]
class JsonLoaderTest extends TestCase
{
    public function testJsonLoader()
    {
        $loader = new JsonLoader();

        $translations = $loader->loadFile(__DIR__.'/../assets/translations.json');

        $this->assertCount(2, $translations);
        $this->assertSame('testingdomain', $translations->getDomain());

        $translation = $translations->find(null, '%ss must be unique for %ss %ss.');

        $this->assertNotNull($translation);
        $this->assertSame('%ss mora da bude jedinstven za %ss %ss.', $translation->getTranslation());
        $this->assertCount(0, $translation->getPluralTranslations());

        $translation = $translations->find('other-context', '日本人は日本で話される言語です！');

        $this->assertNotNull($translation);
        $this->assertSame('singular', $translation->getTranslation());
        $this->assertCount(2, $translation->getPluralTranslations());
        $this->assertSame(['plural1', 'plural2'], $translation->getPluralTranslations());
    }
}
