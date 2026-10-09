<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Loader;

use Omega\Gettext\Loader\Loader;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;
use PHPUnit\Framework\Assert;

/**
 * Shared behavioral helpers for the PO-family loader tests.
 *
 * The plain-PHPUnit abstract case BasePoLoaderTestCase cannot express shared
 * green tests in Pest, so the common flows moved here as static methods that
 * every concrete loader pest file invokes with its own loader instance.
 */
final class PoLoaderBehaviors
{
    public static function assertPoFile(Loader $loader): void
    {
        $translations = $loader->loadFile(__DIR__ . '/../assets/translations.po');

        self::assertPoFileContent($translations);
    }

    public static function assertPoFileContent(Translations $translations): void
    {
        $description = $translations->description;
        Assert::assertSame(
            <<<'EOT'
SOME DESCRIPTIVE TITLE
Copyright (C) YEAR Free Software Foundation, Inc.
This file is distributed under the same license as the PACKAGE package.
FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
EOT,
            $description
        );

        Assert::assertSame(['fuzzy'], $translations->getFlags()->toArray());

        Assert::assertCount(14, $translations);

        $array = $translations->getTranslations();

        self::translation1(self::shiftTranslation($array));
        self::translation2(self::shiftTranslation($array));
        self::translation3(self::shiftTranslation($array));
        self::translation4(self::shiftTranslation($array));
        self::translation5(self::shiftTranslation($array));
        self::translation6(self::shiftTranslation($array));
        self::translation7(self::shiftTranslation($array));
        self::translation8(self::shiftTranslation($array));
        self::translation9(self::shiftTranslation($array));
        self::translation10(self::shiftTranslation($array));
        self::translation11(self::shiftTranslation($array));
        self::translation12(self::shiftTranslation($array));
        self::translation13(self::shiftTranslation($array));
        self::translation14(self::shiftTranslation($array));

        $headers = $translations->getHeaders()->toArray();

        Assert::assertCount(12, $headers);

        Assert::assertSame('text/plain; charset=UTF-8', $headers['Content-Type']);
        Assert::assertSame('8bit', $headers['Content-Transfer-Encoding']);
        Assert::assertSame('', $headers['POT-Creation-Date']);
        Assert::assertSame('', $headers['PO-Revision-Date']);
        Assert::assertSame('', $headers['Last-Translator']);
        Assert::assertSame('', $headers['Language-Team']);
        Assert::assertSame('1.0', $headers['MIME-Version']);
        Assert::assertSame('bs', $headers['Language']);
        Assert::assertSame(
            'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);',
            $headers['Plural-Forms']
        );
        Assert::assertSame('Poedit 1.6.5', $headers['X-Generator']);
        Assert::assertSame('gettext generator test', $headers['Project-Id-Version']);
        Assert::assertSame('testingdomain', $headers['X-Domain']);

        Assert::assertSame('testingdomain', $translations->getDomain());
        Assert::assertSame('bs', $translations->getLanguage());
    }

    public static function assertMultilineDisabled(Loader $loader): void
    {
        $po = <<<'EOT'
#~ msgid "Last agent hours-description"
#~ msgstr ""
#~ "How many hours in the past can system look at finding the last agent? "
#~ "This parameter is only used if 'Call Last Agent' is set to 'YES'."
EOT;
        $translations = $loader->loadString($po);
        $translation = $translations->find(null, 'Last agent hours-description');
        Assert::assertNotNull($translation);

        Assert::assertTrue($translation->disabled);
        Assert::assertSame(
            "How many hours in the past can system look at finding the last agent?"
            . " This parameter is only used if 'Call Last Agent' is set to 'YES'.",
            $translation->translation
        );
    }

    public static function assertStringDecode(Loader $loader, string $source, string $decoded): void
    {
        $po = <<<EOT
msgid "source"
msgstr {$source}
EOT;
        $translations = $loader->loadString($po);
        $translation = $translations->find(null, 'source');
        Assert::assertNotNull($translation);

        Assert::assertSame($decoded, $translation->translation);
    }

    /**
     * Shifts a translation off the array, asserting its presence.
     *
     * @param array<string, Translation> $translations
     */
    private static function shiftTranslation(array &$translations): Translation
    {
        $translation = array_shift($translations);
        Assert::assertNotNull($translation);

        return $translation;
    }

    private static function translation1(Translation $translation): void
    {
        Assert::assertSame(
            'Ensure this value has at least %(limit_value)d character (it has %sd).',
            $translation->getOriginal()
        );
        Assert::assertSame(
            'Ensure this value has at least %(limit_value)d characters (it has %sd).',
            $translation->plural
        );
        Assert::assertSame('', $translation->translation);
        Assert::assertSame(['', ''], $translation->getPluralTranslations());
    }

    private static function translation2(Translation $translation): void
    {
        Assert::assertSame(
            'Ensure this value has at most %(limit_value)d character (it has %sd).',
            $translation->getOriginal()
        );
        Assert::assertSame(
            'Ensure this value has at most %(limit_value)d characters (it has %sd).',
            $translation->plural
        );
        Assert::assertSame('', $translation->translation);
        Assert::assertSame(['', ''], $translation->getPluralTranslations());
    }

    private static function translation3(Translation $translation): void
    {
        Assert::assertSame('%ss must be unique for %ss %ss.', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('%ss mora da bude jedinstven za %ss %ss.', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
    }

    private static function translation4(Translation $translation): void
    {
        Assert::assertSame('and', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('i', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
        Assert::assertSame(['c-format'], $translation->getFlags()->toArray());
    }

    private static function translation5(Translation $translation): void
    {
        Assert::assertSame('Value %sr is not a valid choice.', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
        Assert::assertSame(['This is a extracted comment'], $translation->getExtractedComments()->toArray());
    }

    private static function translation6(Translation $translation): void
    {
        Assert::assertSame('This field cannot be null.', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('Ovo polje ne može ostati prazno.', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
        Assert::assertCount(1, $translation->getReferences());
        Assert::assertSame(['C:/Users/Me/Documents/foo2.php' => [1]], $translation->getReferences()->toArray());
    }

    private static function translation7(Translation $translation): void
    {
        Assert::assertSame('This field cannot be blank.', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('Ovo polje ne može biti prazno.', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
        Assert::assertCount(1, $translation->getReferences());
        Assert::assertSame(['C:/Users/Me/Documents/foo1.php' => []], $translation->getReferences()->toArray());
    }

    private static function translation8(Translation $translation): void
    {
        Assert::assertSame('Field of type: %ss', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('Polje tipa: %ss', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
        Assert::assertCount(2, $translation->getReferences());
        Assert::assertSame([
            'attributes/address/composer.php' => [8],
            'attributes/address/form.php' => [7],
        ], $translation->getReferences()->toArray());
    }

    private static function translation9(Translation $translation): void
    {
        Assert::assertSame('Integer', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('Cijeo broj', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
        Assert::assertCount(0, $translation->getReferences());
        Assert::assertCount(1, $translation->getComments());
        Assert::assertSame(['a simple line comment is above'], $translation->getComments()->toArray());
    }

    private static function translation10(Translation $translation): void
    {
        Assert::assertSame('{test1}', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame("test1\n<div>\n test2\n</div>\ntest3", $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
        Assert::assertCount(0, $translation->getComments());
        Assert::assertCount(3, $translation->getReferences());
        Assert::assertSame([
            '/var/www/test/test.php' => [96, 97],
            '/var/www/test/test2.php' => [98],
        ], $translation->getReferences()->toArray());
    }

    private static function translation11(Translation $translation): void
    {
        Assert::assertSame('{test2}', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame("test1\n<div>\n test2\n</div>\ntest3", $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
        Assert::assertCount(0, $translation->getComments());
        Assert::assertCount(1, $translation->getReferences());
        Assert::assertSame(['/var/www/test/test.php' => [96]], $translation->getReferences()->toArray());
    }

    private static function translation12(Translation $translation): void
    {
        Assert::assertSame('Multibyte test', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame('日本人は日本で話される言語です！', $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
        Assert::assertCount(0, $translation->getComments());
        Assert::assertCount(0, $translation->getReferences());
    }

    private static function translation13(Translation $translation): void
    {
        Assert::assertSame('Tabulation test', $translation->getOriginal());
        Assert::assertNull($translation->plural);
        Assert::assertSame("FIELD\tFIELD", $translation->translation);
        Assert::assertCount(0, $translation->getPluralTranslations());
        Assert::assertCount(0, $translation->getComments());
        Assert::assertCount(0, $translation->getReferences());
    }

    private static function translation14(Translation $translation): void
    {
        Assert::assertSame('%s has been added to your cart.', $translation->getOriginal());
        Assert::assertSame('%s have been added to your cart.', $translation->plural);
        Assert::assertSame('%s has been added to your cart.', $translation->translation);
        Assert::assertSame(['%s have been added to your cart.'], $translation->getPluralTranslations());
        Assert::assertCount(1, $translation->getComments());
        Assert::assertCount(0, $translation->getReferences());
    }
}
