<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Scanner;

use Exception;
use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\References;
use Omega\Gettext\Scanner\CodeScanner;
use Omega\Gettext\Scanner\JsFunctionsScanner;
use Omega\Gettext\Scanner\JsNodeVisitor;
use Omega\Gettext\Scanner\JsScanner;
use Omega\Gettext\Scanner\ParsedFunction;
use Omega\Gettext\Scanner\Scanner;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CodeScanner::class)]
#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(JsFunctionsScanner::class)]
#[CoversClass(JsNodeVisitor::class)]
#[CoversClass(JsScanner::class)]
#[CoversClass(ParsedFunction::class)]
#[CoversClass(References::class)]
#[CoversClass(Scanner::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
class JsScannerTest extends TestCase
{
    public function testScanJavaScriptFunctions(): void
    {
        $js = <<<'JS'
__("Hello");
ngettext("One apple", "%d apples", 3);
pgettext("menu", "File");
noop__("marked");
dgettext("domain2", "Save");
JS;

        $scanner = new JsScanner(
            Translations::create('domain1'),
            Translations::create('domain2')
        );
        $scanner->setDefaultDomain('domain1');

        $scanner->scanString($js, 'virtual.js');

        /**
         * @var Translations $domain1
         * @var Translations $domain2
         */
        ['domain1' => $domain1, 'domain2' => $domain2] = $scanner->getTranslations();

        $this->assertCount(4, $domain1);
        $this->assertCount(1, $domain2);

        $translation = $domain1->find(null, 'Hello');
        $this->assertNotNull($translation);
        $this->assertSame(['virtual.js' => [1]], $translation->getReferences()->toArray());

        $apple = $domain1->find(null, 'One apple');
        $this->assertNotNull($apple);
        $this->assertSame('%d apples', $apple->getPlural());
        $this->assertSame(['virtual.js' => [2]], $apple->getReferences()->toArray());

        $menu = $domain1->find('menu', 'File');
        $this->assertNotNull($menu);
        $this->assertNull($domain1->find(null, 'File'));

        $marked = $domain1->find(null, 'marked');
        $this->assertNotNull($marked);

        $save = $domain2->find(null, 'Save');
        $this->assertNotNull($save);
        $this->assertSame(['virtual.js' => [5]], $save->getReferences()->toArray());
    }

    public function testScanDomainAndContextVariants(): void
    {
        $js = <<<'JS'
dngettext("dom", "One file", "%d files", 2);
npgettext("ctx", "One item", "%d items", 5);
dpgettext("dom", "bar", "Print");
dnpgettext("dom", "bar", "One icon", "%d icons", 4);
__("plain");
JS;

        $scanner = new JsScanner(
            Translations::create('dom'),
            Translations::create('default')
        );
        $scanner->setDefaultDomain('default');

        $scanner->scanString($js, 'virtual.js');

        /**
         * @var Translations $dom
         * @var Translations $default
         */
        ['dom' => $dom, 'default' => $default] = $scanner->getTranslations();

        $this->assertCount(3, $dom);
        $this->assertCount(2, $default);

        $file = $dom->find(null, 'One file');
        $this->assertNotNull($file);
        $this->assertSame('%d files', $file->getPlural());

        $print = $dom->find('bar', 'Print');
        $this->assertNotNull($print);

        $icon = $dom->find('bar', 'One icon');
        $this->assertNotNull($icon);
        $this->assertSame('%d icons', $icon->getPlural());

        $this->assertNotNull($default->find(null, 'plain'));
        $item = $default->find('ctx', 'One item');
        $this->assertNotNull($item);
        $this->assertSame('%d items', $item->getPlural());
    }

    public function testUnknownFunctionsAreIgnored(): void
    {
        $scanner = new JsScanner(Translations::create('messages'));
        $scanner->setDefaultDomain('messages');
        $scanner->scanString("unknownCall('arg'); __('kept');", 'virtual.js');

        $translations = $scanner->getTranslations()['messages'];

        $this->assertCount(1, $translations);
        $this->assertNotNull($translations->find(null, 'kept'));
    }

    public function testInvalidFunctionThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Some required arguments are not valid');

        $scanner = new JsScanner(Translations::create('messages'));
        $scanner->scanString('__(123);', 'virtual.js');
    }

    public function testIgnoredInvalidFunctionsAreSkipped(): void
    {
        $scanner = new JsScanner(Translations::create('messages'));
        $scanner->setDefaultDomain('messages');
        $scanner->ignoreInvalidFunctions();
        $scanner->scanString("ngettext('only-one'); __(123); __('kept');", 'virtual.js');

        $translations = $scanner->getTranslations()['messages'];

        $this->assertCount(1, $translations);
        $this->assertNotNull($translations->find(null, 'kept'));
    }

    public function testAllHandlersSkipInvalidCallsWhenTolerant(): void
    {
        $js = <<<'JS'
ngettext("a");
pgettext("b");
dgettext("c");
dpgettext("d");
npgettext("e");
dngettext("f");
dnpgettext("g");
JS;

        $scanner = new JsScanner(Translations::create('messages'));
        $scanner->setDefaultDomain('messages');
        $scanner->ignoreInvalidFunctions();

        $scanner->scanString($js, 'virtual.js');

        $this->assertCount(0, $scanner->getTranslations()['messages']);
    }

    public function testSpreadArgumentsAreRecordedAsDynamic(): void
    {
        $scanner = new JsScanner(Translations::create('messages'));
        $scanner->setDefaultDomain('messages');

        $scanner->scanString('var rest = ["x"]; __("Hello", ...rest);', 'virtual.js');

        $translations = $scanner->getTranslations()['messages'];

        $this->assertCount(1, $translations);
        $this->assertNotNull($translations->find(null, 'Hello'));
    }

    public function testCallsWithoutResolvableNameAreSkipped(): void
    {
        $scanner = new JsScanner(Translations::create('messages'));
        $scanner->setDefaultDomain('messages');

        $scanner->scanString("(() => 1)(); __('kept');", 'virtual.js');

        $translations = $scanner->getTranslations()['messages'];

        $this->assertCount(1, $translations);
        $this->assertNotNull($translations->find(null, 'kept'));
    }

    public function testLeadingCommentsAreAttachedToExtractedCalls(): void
    {
        $scanner = new JsScanner(Translations::create('messages'));
        $scanner->setDefaultDomain('messages');
        $scanner->extractCommentsStartingWith('');

        $scanner->scanString('/* translators: js */ __("Hello");', 'virtual.js');

        $translation = $scanner->getTranslations()['messages']->find(null, 'Hello');

        $this->assertNotNull($translation);
        $this->assertSame(['translators: js'], $translation->getExtractedComments()->toArray());
    }

    public function testTemplateLiteralArgumentsWithoutExpressions(): void
    {
        $scanner = new JsScanner(Translations::create('messages'));
        $scanner->setDefaultDomain('messages');

        $scanner->scanString('pgettext(`menu`, `File`);', 'virtual.js');

        $translations = $scanner->getTranslations()['messages'];

        $this->assertCount(1, $translations);
        $this->assertNotNull($translations->find('menu', 'File'));
    }

    public function testDynamicArgumentsAreRejectedUnlessTolerant(): void
    {
        $js = <<<'JS'
ngettext(`one ${name}`, `many ${name}`, 3);
__("plain", unknownVar);
JS;

        $strict = new JsScanner(Translations::create('messages'));
        $strict->setDefaultDomain('messages');

        $this->expectException(Exception::class);

        $strict->scanString($js, 'virtual.js');
    }

    public function testDynamicArgumentsAreSkippedWhenTolerant(): void
    {
        $js = <<<'JS'
ngettext(`one ${name}`, `many ${name}`, 3);
__("plain", unknownVar);
__("kept");
JS;

        $scanner = new JsScanner(Translations::create('messages'));
        $scanner->setDefaultDomain('messages');
        $scanner->ignoreInvalidFunctions();

        $scanner->scanString($js, 'virtual.js');

        $translations = $scanner->getTranslations()['messages'];

        $this->assertCount(2, $translations);
        $this->assertNotNull($translations->find(null, 'plain'));
        $this->assertNotNull($translations->find(null, 'kept'));
    }

    public function testComputedMemberCallsAreSkippedButNamedOnesKept(): void
    {
        $scanner = new JsScanner(Translations::create('messages'));
        $scanner->setDefaultDomain('messages');

        $scanner->scanString('obj["fn"](); i.__("kept");', 'virtual.js');

        $translations = $scanner->getTranslations()['messages'];

        $this->assertCount(1, $translations);
        $this->assertNotNull($translations->find(null, 'kept'));
    }

    public function testFunctionMapAndScannerAccessors(): void
    {
        $scanner = new JsScanner(Translations::create('messages'));

        $functions = $scanner->getFunctions();

        $this->assertSame('gettext', $functions['__']);
        $this->assertSame('ngettext', $functions['n__']);
        $this->assertSame('dnpgettext', $functions['dnp__']);
        $this->assertInstanceOf(JsFunctionsScanner::class, $scanner->getFunctionsScanner());
        $this->assertInstanceOf(CodeScanner::class, $scanner);
        $this->assertInstanceOf(Scanner::class, $scanner);

        $this->assertSame('', $scanner->getDefaultDomain());
        $scanner->setDefaultDomain('domain1');
        $this->assertSame('domain1', $scanner->getDefaultDomain());
    }
}
