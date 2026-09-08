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

covers(CodeScanner::class);
covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(JsFunctionsScanner::class);
covers(JsNodeVisitor::class);
covers(JsScanner::class);
covers(ParsedFunction::class);
covers(References::class);
covers(Scanner::class);
covers(Translation::class);
covers(Translations::class);

it('scans javascript functions', function (): void {
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

    expect($domain1)->toHaveCount(4);
    expect($domain2)->toHaveCount(1);

    $translation = $domain1->find(null, 'Hello');
    $this->assertNotNull($translation);
    expect($translation->getReferences()->toArray())->toBe(['virtual.js' => [1]]);

    $apple = $domain1->find(null, 'One apple');
    $this->assertNotNull($apple);
    expect($apple->plural)->toBe('%d apples');
    expect($apple->getReferences()->toArray())->toBe(['virtual.js' => [2]]);

    $menu = $domain1->find('menu', 'File');
    $this->assertNotNull($menu);
    expect($domain1->find(null, 'File'))->toBeNull();

    $marked = $domain1->find(null, 'marked');
    $this->assertNotNull($marked);

    $save = $domain2->find(null, 'Save');
    $this->assertNotNull($save);
    expect($save->getReferences()->toArray())->toBe(['virtual.js' => [5]]);
});

it('scans domain and context variants', function (): void {
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

    expect($dom)->toHaveCount(3);
    expect($default)->toHaveCount(2);

    $file = $dom->find(null, 'One file');
    $this->assertNotNull($file);
    expect($file->plural)->toBe('%d files');

    $print = $dom->find('bar', 'Print');
    $this->assertNotNull($print);

    $icon = $dom->find('bar', 'One icon');
    $this->assertNotNull($icon);
    expect($icon->plural)->toBe('%d icons');

    $plain = $default->find(null, 'plain');
    $this->assertNotNull($plain);

    $item = $default->find('ctx', 'One item');
    $this->assertNotNull($item);
    expect($item->plural)->toBe('%d items');
});

it('ignores unknown functions', function (): void {
    $scanner = new JsScanner(Translations::create('messages'));
    $scanner->setDefaultDomain('messages');
    $scanner->scanString("unknownCall('arg'); __('kept');", 'virtual.js');

    $translations = $scanner->getTranslations()['messages'];

    expect($translations)->toHaveCount(1);
    expect($translations->find(null, 'kept'))->not->toBeNull();
});

it('throws on invalid functions', function (): void {
    expect(fn () => (new JsScanner(Translations::create('messages')))->scanString('__(123);', 'virtual.js'))
        ->toThrow(Exception::class, 'Some required arguments are not valid');
});

it('skips ignored invalid functions', function (): void {
    $scanner = new JsScanner(Translations::create('messages'));
    $scanner->setDefaultDomain('messages');
    $scanner->ignoreInvalidFunctions();
    $scanner->scanString("ngettext('only-one'); __(123); __('kept');", 'virtual.js');

    $translations = $scanner->getTranslations()['messages'];

    expect($translations)->toHaveCount(1);
    expect($translations->find(null, 'kept'))->not->toBeNull();
});

it('skips invalid calls across all handlers when tolerant', function (): void {
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

    expect($scanner->getTranslations()['messages'])->toHaveCount(0);
});

it('records spread arguments as dynamic', function (): void {
    $scanner = new JsScanner(Translations::create('messages'));
    $scanner->setDefaultDomain('messages');

    $scanner->scanString('var rest = ["x"]; __("Hello", ...rest);', 'virtual.js');

    $translations = $scanner->getTranslations()['messages'];

    expect($translations)->toHaveCount(1);
    expect($translations->find(null, 'Hello'))->not->toBeNull();
});

it('skips calls without a resolvable name', function (): void {
    $scanner = new JsScanner(Translations::create('messages'));
    $scanner->setDefaultDomain('messages');

    $scanner->scanString("(() => 1)(); __('kept');", 'virtual.js');

    $translations = $scanner->getTranslations()['messages'];

    expect($translations)->toHaveCount(1);
    expect($translations->find(null, 'kept'))->not->toBeNull();
});

it('attaches leading comments to extracted calls', function (): void {
    $scanner = new JsScanner(Translations::create('messages'));
    $scanner->setDefaultDomain('messages');
    $scanner->extractCommentsStartingWith('');

    $scanner->scanString('/* translators: js */ __("Hello");', 'virtual.js');

    $translation = $scanner->getTranslations()['messages']->find(null, 'Hello');

    $this->assertNotNull($translation);
    expect($translation->getExtractedComments()->toArray())->toBe(['translators: js']);
});

it('handles template literal arguments without expressions', function (): void {
    $scanner = new JsScanner(Translations::create('messages'));
    $scanner->setDefaultDomain('messages');

    $scanner->scanString('pgettext(`menu`, `File`);', 'virtual.js');

    $translations = $scanner->getTranslations()['messages'];

    expect($translations)->toHaveCount(1);
    expect($translations->find('menu', 'File'))->not->toBeNull();
});

it('rejects dynamic arguments unless tolerant', function (): void {
    $js = <<<'JS'
ngettext(`one ${name}`, `many ${name}`, 3);
__("plain", unknownVar);
JS;

    expect(function () use ($js): void {
        $strict = new JsScanner(Translations::create('messages'));
        $strict->setDefaultDomain('messages');

        $strict->scanString($js, 'virtual.js');
    })->toThrow(Exception::class);
});

it('skips dynamic arguments when tolerant', function (): void {
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

    expect($translations)->toHaveCount(2);
    expect($translations->find(null, 'plain'))->not->toBeNull();
    expect($translations->find(null, 'kept'))->not->toBeNull();
});

it('skips computed member calls but keeps named ones', function (): void {
    $scanner = new JsScanner(Translations::create('messages'));
    $scanner->setDefaultDomain('messages');

    $scanner->scanString('obj["fn"](); i.__("kept");', 'virtual.js');

    $translations = $scanner->getTranslations()['messages'];

    expect($translations)->toHaveCount(1);
    expect($translations->find(null, 'kept'))->not->toBeNull();
});

it('exposes the function map and scanner accessors', function (): void {
    $scanner = new JsScanner(Translations::create('messages'));

    $functions = $scanner->getFunctions();

    expect($functions['__'])->toBe('gettext');
    expect($functions['n__'])->toBe('ngettext');
    expect($functions['dnp__'])->toBe('dnpgettext');

    $this->assertInstanceOf(JsFunctionsScanner::class, $scanner->getFunctionsScanner());
    $this->assertInstanceOf(CodeScanner::class, $scanner);
    $this->assertInstanceOf(Scanner::class, $scanner);

    expect($scanner->getDefaultDomain())->toBe('');
    $scanner->setDefaultDomain('domain1');
    expect($scanner->getDefaultDomain())->toBe('domain1');
});
