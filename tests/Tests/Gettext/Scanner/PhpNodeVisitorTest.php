<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Scanner;

use Omega\Gettext\Scanner\ParsedFunction;
use Omega\Gettext\Scanner\PhpFunctionsScanner;
use Omega\Gettext\Scanner\PhpNodeVisitor;

covers(ParsedFunction::class);
covers(PhpFunctionsScanner::class);
covers(PhpNodeVisitor::class);

it('buffers commented calls to unknown functions without extracting them', function (): void {
    $scanner = new PhpFunctionsScanner(['__']);

    $functions = $scanner->scan(
        '<?php return /* translators: hi */ myHelper("x");',
        'virtual.php'
    );

    expect($functions)->toBe([]);
});

it('collects the comment attached to the call', function (): void {
    $scanner = new PhpFunctionsScanner(['__']);

    $functions = $scanner->scan(
        "<?php return /* translators: hi */ __('Hello');",
        'virtual.php'
    );

    expect($functions)->toHaveCount(1);

    $function = $functions[0];

    expect($function->getName())->toBe('__');
    expect($function->getComments())->toBe(['translators: hi']);
    expect($function->getStringArguments(1))->toBe(['Hello']);
});

it('falls back to the numeric item for dynamic array keys', function (): void {
    $scanner = new PhpFunctionsScanner(['__']);

    $functions = $scanner->scan(
        "<?php __(('a' . PHP_VERSION), [time() => 'v']);",
        'virtual.php'
    );

    expect($functions)->toHaveCount(1);
    expect($functions[0]->getArguments())->toHaveCount(2);
});

it('extracts method and static calls by name', function (): void {
    $scanner = new PhpFunctionsScanner(['__']);

    $functions = $scanner->scan(
        '<?php $obj->__("A"); Cls::__("B");',
        'virtual.php'
    );

    expect($functions)->toHaveCount(2);

    foreach ($functions as $function) {
        expect($function->getName())->toBe('__');
    }
});

it('reduces array arguments to literal maps', function (): void {
    $scanner = new PhpFunctionsScanner(['__']);

    $functions = $scanner->scan(
        "<?php __('x', ['plain', 'k' => 'v', time() => 'd', 'ab' . 'cd']);",
        'virtual.php'
    );

    expect($functions)->toHaveCount(1);

    $arguments = $functions[0]->getArguments();

    expect($arguments[0])->toBe('x');

    $array = $arguments[1];

    expect($array)->toBe(['plain', 'k' => 'v', 1 => 'd', 2 => 'abcd']);
});
