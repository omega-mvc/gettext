<?php

declare(strict_types=1);

namespace Tests\Gettext\Loader;

use Gettext\Comments;
use Gettext\Flags;
use Gettext\Headers;
use Gettext\Loader\ArrayLoader;
use Gettext\References;
use Gettext\Translation;
use Gettext\Translations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(References::class)]
#[CoversClass(Comments::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
#[CoversClass(ArrayLoader::class)]
class ArrayLoaderTest extends TestCase
{
    public function testArrayLoader()
    {
        $loader = new ArrayLoader();

        $translations = $loader->loadFile(__DIR__.'/../assets/translations.php');

        $this->assertCount(2, $translations->getHeaders());
        $this->assertSame('testingdomain', $translations->getDomain());
        $this->assertSame('nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);', $translations->getHeaders()->get('Plural-Forms'));
        $this->assertCount(10, $translations);

        $translation = $translations->find(null, 'Integer');

        $this->assertNotNull($translation);
        $this->assertSame('Cijeo broj', $translation->getTranslation());
        $this->assertCount(0, $translation->getPluralTranslations());
    }
}
