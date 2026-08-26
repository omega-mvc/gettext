<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use InvalidArgumentException;
use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Generator\ArrayGenerator;
use Omega\Gettext\Headers;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;
use Omega\Gettext\Translator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayGenerator::class)]
#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
#[CoversClass(Translator::class)]
class TranslatorTest extends TestCase
{
    private const PLURAL_2 = 'nplurals=2; plural=(n != 1);';

    private const PLURAL_3 = 'nplurals=3; plural=(n==1 ? 0 : n%10>=2 && n%10<=4 ? 1 : 2);';

    public function testCreateFromTranslations(): void
    {
        $translations = Translations::create('messages');
        $translations->getHeaders()->set(Headers::HEADER_PLURAL, self::PLURAL_2);

        $hello = Translation::create(null, 'Hello');
        $hello->translate('Ciao');

        $apple = Translation::create(null, 'One apple', '%d apples');
        $apple->translate('Una mela');
        $apple->translatePlural('%d mele');

        $translations->add($hello);
        $translations->add($apple);

        $translator = Translator::createFromTranslations($translations);

        $this->assertSame('Ciao', $translator->gettext('Hello'));
        $this->assertSame('Una mela', $translator->ngettext('One apple', '%d apples', 1));
        $this->assertSame('%d mele', $translator->ngettext('One apple', '%d apples', 10));
    }

    public function testGettextReturnsTranslationOrOriginal(): void
    {
        $translator = new Translator();
        $translator->addTranslations([
            'domain' => 'messages',
            'messages' => [
                '' => [
                    'Hello' => 'Ciao',
                ],
            ],
        ]);

        $this->assertSame('Ciao', $translator->gettext('Hello'));
        $this->assertSame('Missing', $translator->gettext('Missing'));
    }

    public function testEmptyTranslationFallsBackToOriginal(): void
    {
        $translator = new Translator();
        $translator->addTranslations([
            'domain' => 'messages',
            'messages' => [
                '' => [
                    'Void' => '',
                ],
            ],
        ]);

        $this->assertSame('Void', $translator->gettext('Void'));
    }

    public function testContextLookups(): void
    {
        $translator = new Translator();
        $translator->addTranslations([
            'domain' => 'messages',
            'messages' => [
                '' => [
                    'Save' => 'Salvare',
                ],
                'toolbar' => [
                    'Save' => 'Salva',
                    'One item' => ['Una voce', '%d voci'],
                ],
            ],
        ]);

        $this->assertSame('Salvare', $translator->gettext('Save'));
        $this->assertSame('Salva', $translator->pgettext('toolbar', 'Save'));
        $this->assertSame(
            'Una voce',
            $translator->npgettext('toolbar', 'One item', '%d items', 1)
        );
        $this->assertSame(
            '%d voci',
            $translator->npgettext('toolbar', 'One item', '%d items', 3)
        );
        $this->assertSame('Ghost', $translator->pgettext('missing-context', 'Ghost'));
    }

    public function testDomainLookups(): void
    {
        $translator = new Translator();
        $translator->addTranslations([
            'domain' => 'errors',
            'plural-forms' => self::PLURAL_2,
            'messages' => [
                '' => [
                    'One error' => ['Un errore', '%d errori'],
                ],
            ],
        ]);

        $this->assertSame('Un errore', $translator->dgettext('errors', 'One error'));
        $this->assertSame('%d errori', $translator->dngettext('errors', 'One error', '%d errori', 5));
        $this->assertSame('Unknown', $translator->dgettext('missing-domain', 'Unknown'));
        $this->assertSame('Unknown', $translator->dpgettext('missing-domain', 'ctx', 'Unknown'));
    }

    public function testNgettextWithTwoFormFormula(): void
    {
        $translator = new Translator();
        $translator->addTranslations([
            'domain' => 'messages',
            'plural-forms' => self::PLURAL_2,
            'messages' => [
                '' => [
                    'One file' => ['Un file', '%d files'],
                ],
            ],
        ]);

        $this->assertSame('Un file', $translator->ngettext('One file', '%d files', 1));
        $this->assertSame('%d files', $translator->ngettext('One file', '%d files', 2));
        $this->assertSame('%d files', $translator->ngettext('One file', '%d files', 0));
    }

    public function testNgettextWithThreeFormFormula(): void
    {
        $translator = new Translator();
        $translator->addTranslations([
            'domain' => 'messages',
            'plural-forms' => self::PLURAL_3,
            'messages' => [
                '' => [
                    'One file' => ['Jeden plik', '%d pliki', '%d plików'],
                ],
            ],
        ]);

        $this->assertSame('Jeden plik', $translator->ngettext('One file', '%d files', 1));
        $this->assertSame('%d pliki', $translator->ngettext('One file', '%d files', 2));
        $this->assertSame('%d pliki', $translator->ngettext('One file', '%d files', 4));
        $this->assertSame('%d plików', $translator->ngettext('One file', '%d files', 5));
        $this->assertSame('%d plików', $translator->ngettext('One file', '%d files', 0));
    }

    public function testDnpgettextCombinesDomainContextAndPlural(): void
    {
        $translator = new Translator();
        $translator->addTranslations([
            'domain' => 'messages',
            'plural-forms' => self::PLURAL_2,
            'messages' => [
                'sidebar' => [
                    'One link' => ['Un collegamento', '%d collegamenti'],
                ],
            ],
        ]);

        $this->assertSame(
            'Un collegamento',
            $translator->dnpgettext('messages', 'sidebar', 'One link', '%d links', 1)
        );
        $this->assertSame(
            '%d collegamenti',
            $translator->dnpgettext('messages', 'sidebar', 'One link', '%d links', 20)
        );
    }

    public function testMissingEntriesUseSimpleFallbackRule(): void
    {
        $translator = new Translator();
        $translator->addTranslations([
            'domain' => 'messages',
            'plural-forms' => self::PLURAL_3,
            'messages' => [],
        ]);

        $this->assertSame('One house', $translator->ngettext('One house', '%d houses', 1));
        $this->assertSame('%d houses', $translator->ngettext('One house', '%d houses', 2));
        $this->assertSame('%d houses', $translator->dngettext('other-domain', 'One house', '%d houses', 3));
    }

    public function testFirstDomainIsDefaultAndCanBeSwitched(): void
    {
        $translator = new Translator();
        $translator->addTranslations([
            'domain' => 'first',
            'messages' => [
                '' => [
                    'Color' => 'Colour',
                ],
            ],
        ]);
        $translator->addTranslations([
            'domain' => 'second',
            'messages' => [
                '' => [
                    'Color' => 'Colore',
                ],
            ],
        ]);

        $this->assertSame('Colour', $translator->gettext('Color'));

        $translator->defaultDomain('second');

        $this->assertSame('Colore', $translator->gettext('Color'));
        $this->assertSame('Colour', $translator->dgettext('first', 'Color'));
    }

    public function testAddTranslationsMergesSameDomain(): void
    {
        $translator = new Translator();
        $translator->addTranslations([
            'domain' => 'messages',
            'messages' => [
                '' => [
                    'Hello' => 'Ciao',
                ],
            ],
        ]);
        $translator->addTranslations([
            'domain' => 'messages',
            'messages' => [
                '' => [
                    'Bye' => 'Arrivederci',
                    'Hello' => 'Salve',
                ],
            ],
        ]);

        $this->assertSame('Salve', $translator->gettext('Hello'));
        $this->assertSame('Arrivederci', $translator->gettext('Bye'));
    }

    public function testLoadTranslationsFromFile(): void
    {
        $file = sys_get_temp_dir() . '/gettext-translator-test-' . uniqid() . '.php';
        $code = "<?php return " . var_export([
            'domain' => 'filed',
            'messages' => [
                '' => [
                    'Key' => 'Valore',
                ],
            ],
        ], true) . ";";
        file_put_contents($file, $code);

        try {
            $translator = new Translator();
            $returned = $translator->loadTranslations($file);

            $this->assertSame($translator, $returned);
            $this->assertSame('Valore', $translator->gettext('Key'));
        } finally {
            unlink($file);
        }
    }

    public function testLoadTranslationsRejectsNonArrayFiles(): void
    {
        $file = sys_get_temp_dir() . '/gettext-translator-test-' . uniqid() . '.php';
        file_put_contents($file, '<?php return "not-an-array";');

        try {
            $translator = new Translator();

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Invalid translations file: it must return an array');

            $translator->loadTranslations($file);
        } finally {
            unlink($file);
        }
    }

    public function testInvalidPluralExpressionThrows(): void
    {
        $translator = new Translator();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid plural form expression');

        $translator->addTranslations([
            'domain' => 'messages',
            'plural-forms' => 'nplurals=2; plural=n @ 1;',
            'messages' => [],
        ]);
    }

    public function testMalformedDictionariesAreNormalizedSafely(): void
    {
        $translator = new Translator();
        $translator->addTranslations([
            'domain' => 123,
            'messages' => 'not-an-array',
        ]);

        $this->assertSame('Anything', $translator->gettext('Anything'));

        $translator->addTranslations([
            'domain' => 'mixed',
            'messages' => [
                'scalar-context' => 'dropped-context',
                '' => [
                    'kept' => 'KEPT',
                    42 => 123,
                    'plurals' => ['PRIMO', 123, 'SECONDO'],
                ],
            ],
        ]);

        $this->assertSame('KEPT', $translator->dgettext('mixed', 'kept'));
        $this->assertSame('PRIMO', $translator->dngettext('mixed', 'plurals', 'x', 1));
        $this->assertSame('SECONDO', $translator->dngettext('mixed', 'plurals', 'x', 2));
        $this->assertSame('123', $translator->gettext('123'));
    }

    public function testNoopReturnsOriginalUnchanged(): void
    {
        $translator = new Translator();

        $this->assertSame('Just marked', $translator->noop('Just marked'));
    }
}
