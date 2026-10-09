<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Loader;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\Loader\MoLoader;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Tests\TestCase;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(MoLoader::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
final class MoLoaderTest extends TestCase
{
    public function testLoadsACompleteMoFile(): void
    {
        $loader = new MoLoader();
        $translations = $loader->loadFile(__DIR__ . '/../assets/translations.mo');

        $this->assertCount(11, $translations);

        $array = $translations->getTranslations();

        self::moTranslation0(self::shiftMoTranslation($array));
        self::moTranslation1(self::shiftMoTranslation($array));
        self::moTranslation2(self::shiftMoTranslation($array));
        self::moTranslation3(self::shiftMoTranslation($array));
        self::moTranslation4(self::shiftMoTranslation($array));
        self::moTranslation5(self::shiftMoTranslation($array));
        self::moTranslation6(self::shiftMoTranslation($array));
        self::moTranslation7(self::shiftMoTranslation($array));
        self::moTranslation8(self::shiftMoTranslation($array));
        self::moTranslation9(self::shiftMoTranslation($array));
        self::moTranslation10(self::shiftMoTranslation($array));

        $headers = $translations->getHeaders()->toArray();

        $this->assertCount(12, $headers);

        $this->assertSame('text/plain; charset=UTF-8', $headers['Content-Type']);
        $this->assertSame('8bit', $headers['Content-Transfer-Encoding']);
        $this->assertSame('', $headers['POT-Creation-Date']);
        $this->assertSame('', $headers['PO-Revision-Date']);
        $this->assertSame('', $headers['Last-Translator']);
        $this->assertSame('', $headers['Language-Team']);
        $this->assertSame('1.0', $headers['MIME-Version']);
        $this->assertSame('bs', $headers['Language']);
        $this->assertSame(
            'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);',
            $headers['Plural-Forms']
        );
        $this->assertSame('Poedit 1.6.5', $headers['X-Generator']);
        $this->assertSame('gettext generator test', $headers['Project-Id-Version']);
        $this->assertSame('testingdomain', $headers['X-Domain']);

        $this->assertSame('testingdomain', $translations->getDomain());
        $this->assertSame('bs', $translations->getLanguage());
    }

    /**
     * Shifts a translation off the array, asserting its presence.
     *
     * @param array<string, Translation> $translations
     */
    private static function shiftMoTranslation(array &$translations): Translation
    {
        $translation = array_shift($translations);
        Assert::assertNotNull($translation);

        return $translation;
    }

    private static function moTranslation0(Translation $translation): void
    {
        Assert::assertSame('%s has been added to your cart.', $translation->getOriginal());
        Assert::assertSame('%s have been added to your cart.', $translation->plural);
        Assert::assertSame('%s has been added to your cart.', $translation->translation);
        Assert::assertCount(1, $translation->getPluralTranslations());
    }

    private static function moTranslation1(Translation $translation): void
    {
        Assert::assertSame('%ss must be unique for %ss %ss.', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('%ss mora da bude jedinstven za %ss %ss.', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
    }

    private static function moTranslation2(Translation $translation): void
    {
        Assert::assertSame('Field of type: %ss', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('Polje tipa: %ss', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
    }

    private static function moTranslation3(Translation $translation): void
    {
        Assert::assertSame('Integer', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('Cijeo broj', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
    }

    private static function moTranslation4(Translation $translation): void
    {
        Assert::assertSame('Multibyte test', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('日本人は日本で話される言語です！', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
    }

    private static function moTranslation5(Translation $translation): void
    {
        Assert::assertSame('Tabulation test', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame("FIELD\tFIELD", $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
    }

    private static function moTranslation6(Translation $translation): void
    {
        Assert::assertSame('This field cannot be blank.', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('Ovo polje ne može biti prazno.', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
    }

    private static function moTranslation7(Translation $translation): void
    {
        Assert::assertSame('This field cannot be null.', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('Ovo polje ne može ostati prazno.', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
    }

    private static function moTranslation8(Translation $translation): void
    {
        Assert::assertSame('and', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('i', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
    }

    private static function moTranslation9(Translation $translation): void
    {
        Assert::assertSame('{test1}', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame("test1\n<div>\n test2\n</div>\ntest3", $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
    }

    private static function moTranslation10(Translation $translation): void
    {
        Assert::assertSame('{test2}', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame("test1\n<div>\n test2\n</div>\ntest3", $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
    }
}
