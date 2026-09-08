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

covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(MoLoader::class);
covers(References::class);
covers(Translation::class);
covers(Translations::class);

it('loads a complete mo file', function (): void {
    $loader = new MoLoader();
    $translations = $loader->loadFile(__DIR__ . '/../assets/translations.mo');

    expect($translations)->toHaveCount(11);

    $array = $translations->getTranslations();

    moTranslation0(shiftMoTranslation($array));
    moTranslation1(shiftMoTranslation($array));
    moTranslation2(shiftMoTranslation($array));
    moTranslation3(shiftMoTranslation($array));
    moTranslation4(shiftMoTranslation($array));
    moTranslation5(shiftMoTranslation($array));
    moTranslation6(shiftMoTranslation($array));
    moTranslation7(shiftMoTranslation($array));
    moTranslation8(shiftMoTranslation($array));
    moTranslation9(shiftMoTranslation($array));
    moTranslation10(shiftMoTranslation($array));

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
});

/**
 * Shifts a translation off the array, asserting its presence.
 *
 * @param array<string, Translation> $translations
 */
function shiftMoTranslation(array &$translations): Translation
{
    $translation = array_shift($translations);
    Assert::assertNotNull($translation);

    return $translation;
}

function moTranslation0(Translation $translation): void
{
    expect($translation->getOriginal())->toBe('%s has been added to your cart.');
    expect($translation->plural)->toBe('%s have been added to your cart.');
    expect($translation->translation)->toBe('%s has been added to your cart.');
    expect($translation->getPluralTranslations())->toHaveCount(1);
}

function moTranslation1(Translation $translation): void
{
    expect($translation->getOriginal())->toBe('%ss must be unique for %ss %ss.');
    expect($translation->plural)->toBeNull();
    expect($translation->translation)->toBe('%ss mora da bude jedinstven za %ss %ss.');
    expect($translation->getPluralTranslations())->toHaveCount(0);
}

function moTranslation2(Translation $translation): void
{
    expect($translation->getOriginal())->toBe('Field of type: %ss');
    expect($translation->plural)->toBeNull();
    expect($translation->translation)->toBe('Polje tipa: %ss');
    expect($translation->getPluralTranslations())->toHaveCount(0);
}

function moTranslation3(Translation $translation): void
{
    expect($translation->getOriginal())->toBe('Integer');
    expect($translation->plural)->toBeNull();
    expect($translation->translation)->toBe('Cijeo broj');
    expect($translation->getPluralTranslations())->toHaveCount(0);
}

function moTranslation4(Translation $translation): void
{
    expect($translation->getOriginal())->toBe('Multibyte test');
    expect($translation->plural)->toBeNull();
    expect($translation->translation)->toBe('日本人は日本で話される言語です！');
    expect($translation->getPluralTranslations())->toHaveCount(0);
}

function moTranslation5(Translation $translation): void
{
    expect($translation->getOriginal())->toBe('Tabulation test');
    expect($translation->plural)->toBeNull();
    expect($translation->translation)->toBe("FIELD\tFIELD");
    expect($translation->getPluralTranslations())->toHaveCount(0);
}

function moTranslation6(Translation $translation): void
{
    expect($translation->getOriginal())->toBe('This field cannot be blank.');
    expect($translation->plural)->toBeNull();
    expect($translation->translation)->toBe('Ovo polje ne može biti prazno.');
    expect($translation->getPluralTranslations())->toHaveCount(0);
}

function moTranslation7(Translation $translation): void
{
    expect($translation->getOriginal())->toBe('This field cannot be null.');
    expect($translation->plural)->toBeNull();
    expect($translation->translation)->toBe('Ovo polje ne može ostati prazno.');
    expect($translation->getPluralTranslations())->toHaveCount(0);
}

function moTranslation8(Translation $translation): void
{
    expect($translation->getOriginal())->toBe('and');
    expect($translation->plural)->toBeNull();
    expect($translation->translation)->toBe('i');
    expect($translation->getPluralTranslations())->toHaveCount(0);
}

function moTranslation9(Translation $translation): void
{
    expect($translation->getOriginal())->toBe('{test1}');
    expect($translation->plural)->toBeNull();
    expect($translation->translation)->toBe("test1\n<div>\n test2\n</div>\ntest3");
    expect($translation->getPluralTranslations())->toHaveCount(0);
}

function moTranslation10(Translation $translation): void
{
    expect($translation->getOriginal())->toBe('{test2}');
    expect($translation->plural)->toBeNull();
    expect($translation->translation)->toBe("test1\n<div>\n test2\n</div>\ntest3");
    expect($translation->getPluralTranslations())->toHaveCount(0);
}
