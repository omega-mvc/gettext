<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\GettextTranslator;
use Omega\Gettext\References;
use Omega\Gettext\TranslatorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(GettextTranslator::class)]
#[CoversClass(References::class)]
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
        $localeFixtures = [
            'it' => [
                'candidates' => ['it_IT.UTF-8', 'it_IT', 'it.UTF-8', 'it'],
                'gettext hello' => 'ciao',
                'pgettext menu|file' => 'archivio',
                'dgettext testdom|salva' => 'salva-ctx',
                'dpgettext testdom|toolbar|stampa' => 'stampa-toolbar',
                'ngettext 1' => 'UNO',
                'ngettext 0' => 'DUE',
                'npgettext 1' => 'una-voce',
                'npgettext 4' => '%d voci',
                'dngettext 1' => 'un-icona',
                'dnpgettext 1' => 'esporta-barra',
            ],
            'de' => [
                'candidates' => ['de_DE.UTF-8', 'de_DE', 'de.UTF-8', 'de'],
                'gettext hello' => 'hallo',
                'pgettext menu|file' => 'archiv',
                'dgettext testdom|salva' => 'speichern-ctx',
                'dpgettext testdom|toolbar|stampa' => 'drucken-toolbar',
                'ngettext 1' => 'EINS',
                'ngettext 0' => 'ZWEI',
                'npgettext 1' => 'ein-eintrag',
                'npgettext 4' => '%d-eintraege',
                'dngettext 1' => 'ein-icon',
                'dnpgettext 1' => 'exportieren-leiste',
            ],
            'fr' => [
                'candidates' => ['fr_FR.UTF-8', 'fr_FR', 'fr.UTF-8', 'fr'],
                'gettext hello' => 'bonjour',
                'pgettext menu|file' => 'fichier',
                'dgettext testdom|salva' => 'sauvegarder-ctx',
                'dpgettext testdom|toolbar|stampa' => 'imprimer-toolbar',
                'ngettext 1' => 'UN',
                'ngettext 0' => 'DEUX',
                'npgettext 1' => 'une-entree',
                'npgettext 4' => '%d entrees',
                'dngettext 1' => 'une-icone',
                'dnpgettext 1' => 'exporter-barre',
            ],
            'en' => [
                'candidates' => ['en_US.UTF-8', 'en_US', 'en.UTF-8', 'en'],
                'gettext hello' => 'hello-en',
                'pgettext menu|file' => 'file-en',
                'dgettext testdom|salva' => 'save-ctx-en',
                'dpgettext testdom|toolbar|stampa' => 'print-toolbar-en',
                'ngettext 1' => 'ONE',
                'ngettext 0' => 'TWO',
                'npgettext 1' => 'one-entry',
                'npgettext 4' => '%d entries',
                'dngettext 1' => 'one-icon-en',
                'dnpgettext 1' => 'export-bar-en',
            ],
            'ru' => [
                'candidates' => ['ru_RU.UTF-8', 'ru_RU', 'ru.UTF-8', 'ru'],
                'gettext hello' => 'привет',
                'pgettext menu|file' => 'файл',
                'dgettext testdom|salva' => 'сохранить-ctx',
                'dpgettext testdom|toolbar|stampa' => 'печать-toolbar',
                'ngettext 1' => 'ОДИН',
                'ngettext 0' => 'ДВА',
                'npgettext 1' => 'одна-запись',
                'npgettext 4' => '%d записей',
                'dngettext 1' => 'одна-иконка',
                'dnpgettext 1' => 'экспорт-панель',
            ],
            'zh' => [
                'candidates' => ['zh_CN.UTF-8', 'zh_CN', 'zh.UTF-8', 'zh'],
                'gettext hello' => '你好',
                'pgettext menu|file' => '文件',
                'dgettext testdom|salva' => '保存-ctx',
                'dpgettext testdom|toolbar|stampa' => '打印-toolbar',
                'ngettext 1' => '一',
                'ngettext 0' => '二',
                'npgettext 1' => '一条记录',
                'npgettext 4' => '%d 条记录',
                'dngettext 1' => '一个图标',
                'dnpgettext 1' => '导出-面板',
            ],

        ];

        $previousLocale = setlocale(LC_ALL, '0');
        $fixtureDir = __DIR__ . '/assets/locales';
        $tested = false;

        try {
            foreach ($localeFixtures as $lang => $expected) {
                $moFile = "$fixtureDir/$lang.mo";
                if (!is_file($moFile)) {
                    continue;
                }

                $activeLocale = null;

                foreach ($expected['candidates'] as $candidate) {
                    if (setlocale(LC_ALL, $candidate) !== false) {
                        $activeLocale = $candidate;
                        break;
                    }
                }

                if ($activeLocale === null) {
                    continue;
                }

                $tested = true;
                $baseDir = sys_get_temp_dir() . '/gettext-native-' . uniqid();
                $directory = "$baseDir/$activeLocale/LC_MESSAGES";
                mkdir($directory, 0777, true);
                copy($moFile, "$directory/testdom.mo");

                try {
                    $translator = new GettextTranslator($activeLocale);
                    $translator->loadDomain('testdom', $baseDir);

                    $this->assertSame($expected['gettext hello'], $translator->gettext('hello'));
                    $this->assertSame(
                        $expected['pgettext menu|file'],
                        $translator->pgettext('menu', 'file')
                    );
                    $this->assertSame(
                        $expected['dgettext testdom|salva'],
                        $translator->dgettext('testdom', 'salva')
                    );
                    $this->assertSame(
                        $expected['dpgettext testdom|toolbar|stampa'],
                        $translator->dpgettext('testdom', 'toolbar', 'stampa')
                    );
                    $this->assertSame($expected['ngettext 1'], $translator->ngettext('uno', 'molti', 1));
                    $this->assertSame($expected['ngettext 0'], $translator->ngettext('uno', 'molti', 0));
                    $this->assertSame(
                        $expected['npgettext 1'],
                        $translator->npgettext('contatore', 'una voce', '%d voci', 1)
                    );
                    $this->assertSame(
                        $expected['npgettext 4'],
                        $translator->npgettext('contatore', 'una voce', '%d voci', 4)
                    );
                    $this->assertSame(
                        $expected['dngettext 1'],
                        $translator->dngettext('testdom', 'un icona', '%d icone', 1)
                    );
                    $this->assertSame(
                        $expected['dnpgettext 1'],
                        $translator->dnpgettext('testdom', 'barra', 'esporta', 'esporta-tutti', 1)
                    );
                } finally {
                    setlocale(LC_ALL, $previousLocale ?: 'C');
                    putenv('LANGUAGE');
                    self::rmdirRecursive($baseDir);
                }
            }

            if (!$tested) {
                self::markTestSkipped('no supported locale (it/de/fr) is available on this system');
            }
        } finally {
            setlocale(LC_ALL, $previousLocale ?: 'C');
        }
    }

    public function testSetLanguageAcceptsExplicitCategory(): void
    {
        $translator = new GettextTranslator();

        $this->assertSame($translator, $translator->setLanguage('C', LC_ALL));
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
