<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Generator\MoGenerator;
use Omega\Gettext\GettextTranslator;
use Omega\Gettext\Headers;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;
use Omega\Gettext\TranslatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(GettextTranslator::class)]
#[CoversClass(Headers::class)]
#[CoversClass(MoGenerator::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
#[RequiresPhpExtension('gettext')]
class GettextTranslatorTest extends TestCase
{
    public function testNoopReturnsOriginalUnchanged(): void
    {
        $translator = new GettextTranslator('C');

        $this->assertSame('Just marked', $translator->noop('Just marked'));
    }

    public function testImplementsTranslatorInterface(): void
    {
        $this->assertInstanceOf(TranslatorInterface::class, new GettextTranslator('C'));
    }

    public function testUntranslatedLookupsReturnOriginal(): void
    {
        $translator = new GettextTranslator('C');

        $this->assertSame('untranslated-singular-xyz', $translator->gettext('untranslated-singular-xyz'));
        $this->assertSame(
            'untranslated-domain-xyz',
            $translator->dgettext('unused-domain', 'untranslated-domain-xyz')
        );
        $this->assertSame(
            'untranslated-context-xyz',
            $translator->pgettext('unused-context', 'untranslated-context-xyz')
        );
        $this->assertSame(
            'untranslated-dcontext-xyz',
            $translator->dpgettext('unused-domain', 'unused-context', 'untranslated-dcontext-xyz')
        );
    }

    public function testUntranslatedPluralsFollowDefaultRule(): void
    {
        $translator = new GettextTranslator('C');

        $this->assertSame('untranslated-one', $translator->ngettext('untranslated-one', '%d many', 1));
        $this->assertSame('%d many', $translator->ngettext('untranslated-one', '%d many', 0));
        $this->assertSame('%d many', $translator->ngettext('untranslated-one', '%d many', 2));
        $this->assertSame('%d many', $translator->dngettext('unused-domain', 'untranslated-one', '%d many', 3));
        $this->assertSame(
            'untranslated-one',
            $translator->npgettext('unused-context', 'untranslated-one', '%d many', 1)
        );
        $this->assertSame(
            '%d many',
            $translator->dnpgettext('unused-domain', 'unused-context', 'untranslated-one', '%d many', 5)
        );
    }

    public function testSetLanguageIsFluent(): void
    {
        $translator = new GettextTranslator();

        $this->assertSame($translator, $translator->setLanguage('C'));
    }

    public function testLoadDomainBindsDirectory(): void
    {
        $directory = sys_get_temp_dir() . '/gettext-translator-test-' . uniqid();
        mkdir($directory);

        try {
            $translator = new GettextTranslator('C');
            $returned = $translator->loadDomain('test-gettext-domain', $directory);

            $this->assertSame($returned, $translator);
            $this->assertSame('empty-catalog-xyz', $translator->gettext('empty-catalog-xyz'));
        } finally {
            rmdir($directory);
        }
    }

    public function testConstructorReadsLanguageFromEnvironment(): void
    {
        putenv('LANGUAGE=C');

        try {
            $translator = new GettextTranslator();

            $this->assertSame('env-lookup-xyz', $translator->gettext('env-lookup-xyz'));
        } finally {
            putenv('LANGUAGE');
        }
    }

    #[RequiresPhpExtension('gettext')]
    public function testCatalogLookupsReturnTranslations(): void
    {
        $baseDir = sys_get_temp_dir() . '/gettext-native-' . uniqid();
        $moBinary = $this->buildCatalog();

        foreach (['it_IT', 'it_IT.UTF-8'] as $localeDir) {
            $directory = "$baseDir/$localeDir/LC_MESSAGES";
            mkdir($directory, 0777, true);
            file_put_contents("$directory/testdom.mo", $moBinary);
        }

        $previousLocale = setlocale(LC_ALL, '0');

        if (setlocale(LC_ALL, 'it_IT.UTF-8') === false) {
            setlocale(LC_ALL, $previousLocale ?: 'C');
            self::markTestSkipped('the it_IT.UTF-8 locale is not available on this system');
        }

        try {
            $translator = new GettextTranslator('it_IT.UTF-8');
            $translator->loadDomain('testdom', $baseDir);

            $this->assertSame('ciao', $translator->gettext('hello'));
            $this->assertSame('archivio', $translator->pgettext('menu', 'file'));
            $this->assertSame('salva-ctx', $translator->dgettext('testdom', 'salva'));
            $this->assertSame('stampa-toolbar', $translator->dpgettext('testdom', 'toolbar', 'stampa'));
            $this->assertSame('UNO', $translator->ngettext('uno', 'molti', 1));
            $this->assertSame('DUE', $translator->ngettext('uno', 'molti', 0));
            $this->assertSame('una-voce', $translator->npgettext('contatore', 'una voce', '%d voci', 1));
            $this->assertSame('%d voci', $translator->npgettext('contatore', 'una voce', '%d voci', 4));
            $this->assertSame('un-icona', $translator->dngettext('testdom', 'un icona', '%d icone', 1));
            $this->assertSame(
                'esporta-barra',
                $translator->dnpgettext('testdom', 'barra', 'esporta', 'esporta-tutti', 1)
            );
        } finally {
            setlocale(LC_ALL, $previousLocale ?: 'C');
            putenv('LANGUAGE');
            self::rmdirRecursive($baseDir);
        }
    }

    public function testSetLanguageAcceptsExplicitCategory(): void
    {
        $translator = new GettextTranslator();

        $this->assertSame($translator, $translator->setLanguage('C', LC_ALL));
    }

    private function buildCatalog(): string
    {
        $translations = Translations::create('testdom');
        $translations->getHeaders()->set(Headers::HEADER_PLURAL, 'nplurals=2; plural=(n != 1);');

        $entries = [
            [Translation::create(null, 'hello'), 'ciao'],
            [Translation::create('menu', 'file'), 'archivio'],
            [Translation::create(null, 'salva'), 'salva-ctx'],
            [Translation::create('toolbar', 'stampa'), 'stampa-toolbar'],
            [Translation::create(null, 'uno', 'molti'), "UNO\x00DUE"],
            [Translation::create('contatore', 'una voce', '%d voci'), "una-voce\x00%d voci"],
            [Translation::create(null, 'un icona', '%d icone'), 'un-icona'],
            [Translation::create('barra', 'esporta', 'esporta-tutti'), 'esporta-barra'],
        ];

        foreach ($entries as [$translation, $text]) {
            $parts = explode("\x00", $text);

            if ($translation->getPlural() !== null && count($parts) > 1) {
                $translation->translate($parts[0]);
                $translation->translatePlural(...array_slice($parts, 1));
            } else {
                $translation->translate($text);
            }

            $translations->add($translation);
        }

        $generator = new MoGenerator();
        $generator->includeHeaders(true);

        return $generator->generateString($translations);
    }

    private static function rmdirRecursive(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;
            is_dir($path) ? self::rmdirRecursive($path) : unlink($path);
        }

        rmdir($directory);
    }
}
