<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Scanner;

use Omega\Gettext\Scanner\JsFunctionsScanner;
use Omega\Gettext\Scanner\JsNodeVisitor;
use Omega\Gettext\Scanner\ParsedFunction;
use PHPUnit\Framework\Assert;

covers(ParsedFunction::class);
covers(JsNodeVisitor::class);
covers(JsFunctionsScanner::class);

it('extracts js functions', function (): void {
    $scanner = new JsFunctionsScanner();
    $file = __DIR__ . '/../assets/functions.js';
    $code = file_get_contents($file);
    $this->assertNotFalse($code);
    $functions = $scanner->scan($code, $file);

    expect($functions)->toHaveCount(14);

    //fn1
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn1');
    expect($function->countArguments())->toBe(3);
    expect($function->getArguments())->toBe(['arg1', 'arg2', 3]);
    expect($function->getLine())->toBe(4);
    expect($function->getLastLine())->toBe(4);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(1);

    $comments = $function->getComments();
    expect(array_shift($comments))->toBe('This comment is related with the first function');

    //fn2
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn2');
    expect($function->countArguments())->toBe(1);
    expect($function->getLine())->toBe(5);
    expect($function->getLastLine())->toBe(5);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);

    //fn3
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn3');
    expect($function->countArguments())->toBe(3);
    expect($function->getArguments())->toBe([null, 'arg5', null]);
    expect($function->getLine())->toBe(6);
    expect($function->getLastLine())->toBe(6);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);

    //fn4
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn4');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe(['arg4']);
    expect($function->getLine())->toBe(6);
    expect($function->getLastLine())->toBe(6);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);

    //fn5
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn5');
    expect($function->countArguments())->toBe(2);
    expect($function->getArguments())->toBe([6, 7.5]);
    expect($function->getLine())->toBe(6);
    expect($function->getLastLine())->toBe(6);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);

    //fn6
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn6');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe([null]);
    expect($function->getLine())->toBe(7);
    expect($function->getLastLine())->toBe(7);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);

    //fn7
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn7');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe([null]);
    expect($function->getLine())->toBe(8);
    expect($function->getLastLine())->toBe(8);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);

    //fn9
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn9');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe([null]);
    expect($function->getLine())->toBe(11);
    expect($function->getLastLine())->toBe(11);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(2);

    $comments = $function->getComments();
    expect(array_shift($comments))->toBe('fn_8();');
    expect(array_shift($comments))->toBe('ALLOW: This is a comment to fn9');

    //fn10
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn10');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe([null]);
    expect($function->getLine())->toBe(13);
    expect($function->getLastLine())->toBe(13);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(1);

    $comments = $function->getComments();
    expect(array_shift($comments))->toBe('Comment to fn10');

    //fn11
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn11');
    expect($function->countArguments())->toBe(3);
    expect($function->getArguments())->toBe(['arg9', 'arg10', null]);
    expect($function->getLine())->toBe(16);
    expect($function->getLastLine())->toBe(16);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(2);

    $comments = $function->getComments();
    expect(array_shift($comments))->toBe('Related comment 1');
    expect(array_shift($comments))->toBe('ALLOW: Related comment 2');

    //fn12
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn12');
    expect($function->countArguments())->toBe(2);
    expect($function->getArguments())->toBe(['arg11', 'arg12']);
    expect($function->getLine())->toBe(22);
    expect($function->getLastLine())->toBe(28);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(3);

    $comments = $function->getComments();
    expect(array_shift($comments))->toBe("Related comment\nnumber one");
    expect(array_shift($comments))->toBe('Related comment 2');
    expect(array_shift($comments))->toBe('ALLOW: Related comment 3');

    //fn13
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn13');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe([null]);
    expect($function->getLine())->toBe(30);
    expect($function->getLastLine())->toBe(30);
    expect($function->getFilename())->toBe($file);

    //fn14
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn14');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe([null]);
    expect($function->getLine())->toBe(30);
    expect($function->getLastLine())->toBe(30);
    expect($function->getFilename())->toBe($file);

    //fn15
    $function = shiftJsFunction($functions);
    expect($function->getName())->toBe('fn15');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe(['foo']);
    expect($function->getLine())->toBe(30);
    expect($function->getLastLine())->toBe(30);
    expect($function->getFilename())->toBe($file);
});

it('configures the parser version fluently', function (): void {
    $scanner = (new JsFunctionsScanner(['__']))->parser('latest');

    $functions = $scanner->scan('__("kept");', 'virtual.js');

    expect($functions)->toHaveCount(1);
    expect($functions[0]->getStringArguments(1))->toBe(['kept']);
});

/**
 * Shifts a parsed function off the array, asserting its presence.
 *
 * @param array<ParsedFunction> $functions
 */
function shiftJsFunction(array &$functions): ParsedFunction
{
    $function = $functions[0] ?? null;

    if (!$function instanceof ParsedFunction) {
        Assert::fail('No more parsed functions available');
    }

    array_shift($functions);

    return $function;
}
