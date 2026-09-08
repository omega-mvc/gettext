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
        expect($description)->toBe(
            <<<'EOT'
SOME DESCRIPTIVE TITLE
Copyright (C) YEAR Free Software Foundation, Inc.
This file is distributed under the same license as the PACKAGE package.
FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
EOT
        );

        expect($translations->getFlags()->toArray())->toBe(['fuzzy']);

        expect($translations)->toHaveCount(14);

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

        expect($headers)->toHaveCount(12);

        expect($headers['Content-Type'])->toBe('text/plain; charset=UTF-8');
        expect($headers['Content-Transfer-Encoding'])->toBe('8bit');
        expect($headers['POT-Creation-Date'])->toBe('');
        expect($headers['PO-Revision-Date'])->toBe('');
        expect($headers['Last-Translator'])->toBe('');
        expect($headers['Language-Team'])->toBe('');
        expect($headers['MIME-Version'])->toBe('1.0');
        expect($headers['Language'])->toBe('bs');
        expect($headers['Plural-Forms'])->toBe(
            'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);'
        );
        expect($headers['X-Generator'])->toBe('Poedit 1.6.5');
        expect($headers['Project-Id-Version'])->toBe('gettext generator test');
        expect($headers['X-Domain'])->toBe('testingdomain');

        expect($translations->getDomain())->toBe('testingdomain');
        expect($translations->getLanguage())->toBe('bs');
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

        expect($translation->disabled)->toBeTrue();
        expect($translation->translation)->toBe(
            "How many hours in the past can system look at finding the last agent?"
            . " This parameter is only used if 'Call Last Agent' is set to 'YES'."
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
        expect($translation->translation)->toBe($decoded);
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
        expect($translation->getOriginal())->toBe(
            'Ensure this value has at least %(limit_value)d character (it has %sd).'
        );
        expect($translation->plural)->toBe(
            'Ensure this value has at least %(limit_value)d characters (it has %sd).'
        );
        expect($translation->translation)->toBe('');
        expect($translation->getPluralTranslations())->toBe(['', '']);
    }

    private static function translation2(Translation $translation): void
    {
        expect($translation->getOriginal())->toBe(
            'Ensure this value has at most %(limit_value)d character (it has %sd).'
        );
        expect($translation->plural)->toBe(
            'Ensure this value has at most %(limit_value)d characters (it has %sd).'
        );
        expect($translation->translation)->toBe('');
        expect($translation->getPluralTranslations())->toBe(['', '']);
    }

    private static function translation3(Translation $translation): void
    {
        expect($translation->getOriginal())->toBe('%ss must be unique for %ss %ss.');
        expect($translation->plural)->toBeNull();
        expect($translation->translation)->toBe('%ss mora da bude jedinstven za %ss %ss.');
        expect($translation->getPluralTranslations())->toHaveCount(0);
    }

    private static function translation4(Translation $translation): void
    {
        expect($translation->getOriginal())->toBe('and');
        expect($translation->plural)->toBeNull();
        expect($translation->translation)->toBe('i');
        expect($translation->getPluralTranslations())->toHaveCount(0);
        expect($translation->getFlags()->toArray())->toBe(['c-format']);
    }

    private static function translation5(Translation $translation): void
    {
        expect($translation->getOriginal())->toBe('Value %sr is not a valid choice.');
        expect($translation->plural)->toBeNull();
        expect($translation->translation)->toBe('');
        expect($translation->getPluralTranslations())->toHaveCount(0);
        expect($translation->getExtractedComments()->toArray())->toBe(['This is a extracted comment']);
    }

    private static function translation6(Translation $translation): void
    {
        expect($translation->getOriginal())->toBe('This field cannot be null.');
        expect($translation->plural)->toBeNull();
        expect($translation->translation)->toBe('Ovo polje ne može ostati prazno.');
        expect($translation->getPluralTranslations())->toHaveCount(0);
        expect($translation->getReferences())->toHaveCount(1);
        expect($translation->getReferences()->toArray())->toBe(['C:/Users/Me/Documents/foo2.php' => [1]]);
    }

    private static function translation7(Translation $translation): void
    {
        expect($translation->getOriginal())->toBe('This field cannot be blank.');
        expect($translation->plural)->toBeNull();
        expect($translation->translation)->toBe('Ovo polje ne može biti prazno.');
        expect($translation->getPluralTranslations())->toHaveCount(0);
        expect($translation->getReferences())->toHaveCount(1);
        expect($translation->getReferences()->toArray())->toBe(['C:/Users/Me/Documents/foo1.php' => []]);
    }

    private static function translation8(Translation $translation): void
    {
        expect($translation->getOriginal())->toBe('Field of type: %ss');
        expect($translation->plural)->toBeNull();
        expect($translation->translation)->toBe('Polje tipa: %ss');
        expect($translation->getPluralTranslations())->toHaveCount(0);
        expect($translation->getReferences())->toHaveCount(2);
        expect($translation->getReferences()->toArray())->toBe([
            'attributes/address/composer.php' => [8],
            'attributes/address/form.php' => [7],
        ]);
    }

    private static function translation9(Translation $translation): void
    {
        expect($translation->getOriginal())->toBe('Integer');
        expect($translation->plural)->toBeNull();
        expect($translation->translation)->toBe('Cijeo broj');
        expect($translation->getPluralTranslations())->toHaveCount(0);
        expect($translation->getReferences())->toHaveCount(0);
        expect($translation->getComments())->toHaveCount(1);
        expect($translation->getComments()->toArray())->toBe(['a simple line comment is above']);
    }

    private static function translation10(Translation $translation): void
    {
        expect($translation->getOriginal())->toBe('{test1}');
        expect($translation->plural)->toBeNull();
        expect($translation->translation)->toBe("test1\n<div>\n test2\n</div>\ntest3");
        expect($translation->getPluralTranslations())->toHaveCount(0);
        expect($translation->getComments())->toHaveCount(0);
        expect($translation->getReferences())->toHaveCount(3);
        expect($translation->getReferences()->toArray())->toBe([
            '/var/www/test/test.php' => [96, 97],
            '/var/www/test/test2.php' => [98],
        ]);
    }

    private static function translation11(Translation $translation): void
    {
        expect($translation->getOriginal())->toBe('{test2}');
        expect($translation->plural)->toBeNull();
        expect($translation->translation)->toBe("test1\n<div>\n test2\n</div>\ntest3");
        expect($translation->getPluralTranslations())->toHaveCount(0);
        expect($translation->getComments())->toHaveCount(0);
        expect($translation->getReferences())->toHaveCount(1);
        expect($translation->getReferences()->toArray())->toBe(['/var/www/test/test.php' => [96]]);
    }

    private static function translation12(Translation $translation): void
    {
        expect($translation->getOriginal())->toBe('Multibyte test');
        expect($translation->plural)->toBeNull();
        expect($translation->translation)->toBe('日本人は日本で話される言語です！');
        expect($translation->getPluralTranslations())->toHaveCount(0);
        expect($translation->getComments())->toHaveCount(0);
        expect($translation->getReferences())->toHaveCount(0);
    }

    private static function translation13(Translation $translation): void
    {
        expect($translation->getOriginal())->toBe('Tabulation test');
        expect($translation->plural)->toBeNull();
        expect($translation->translation)->toBe("FIELD\tFIELD");
        expect($translation->getPluralTranslations())->toHaveCount(0);
        expect($translation->getComments())->toHaveCount(0);
        expect($translation->getReferences())->toHaveCount(0);
    }

    private static function translation14(Translation $translation): void
    {
        expect($translation->getOriginal())->toBe('%s has been added to your cart.');
        expect($translation->plural)->toBe('%s have been added to your cart.');
        expect($translation->translation)->toBe('%s has been added to your cart.');
        expect($translation->getPluralTranslations())->toBe(['%s have been added to your cart.']);
        expect($translation->getComments())->toHaveCount(1);
        expect($translation->getReferences())->toHaveCount(0);
    }
}
