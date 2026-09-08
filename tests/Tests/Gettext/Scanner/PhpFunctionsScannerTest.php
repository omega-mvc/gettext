<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Scanner;

use Omega\Gettext\Scanner\ParsedFunction;
use Omega\Gettext\Scanner\PhpFunctionsScanner;
use Omega\Gettext\Scanner\PhpNodeVisitor;
use PHPUnit\Framework\Assert;

covers(ParsedFunction::class);
covers(PhpNodeVisitor::class);
covers(PhpFunctionsScanner::class);

it('scans empty code', function (): void {
    $scanner = new PhpFunctionsScanner();
    $file = __DIR__ . '/../assets/functions.php';
    $functions = $scanner->scan('', $file);

    expect($functions)->toBe([]);
});

it('extracts php functions', function (): void {
    $scanner = new PhpFunctionsScanner();
    $file = __DIR__ . '/../assets/functions.php';
    $code = file_get_contents($file);
    $this->assertNotFalse($code);
    $functions = $scanner->scan($code, $file);

    expect($functions)->toHaveCount(14);

    //fn1
    $function = shiftPhpFunction($functions);
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
    $function = shiftPhpFunction($functions);
    expect($function->getName())->toBe('fn2');
    expect($function->countArguments())->toBe(1);
    expect($function->getLine())->toBe(5);
    expect($function->getLastLine())->toBe(5);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);

    //fn3
    $function = shiftPhpFunction($functions);
    expect($function->getName())->toBe('fn3');
    expect($function->countArguments())->toBe(3);
    expect($function->getArguments())->toBe([null, 'arg5', null]);
    expect($function->getLine())->toBe(6);
    expect($function->getLastLine())->toBe(6);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);

    //fn4
    $function = shiftPhpFunction($functions);
    expect($function->getName())->toBe('fn4');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe(['arg4']);
    expect($function->getLine())->toBe(6);
    expect($function->getLastLine())->toBe(6);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);

    //fn5
    $function = shiftPhpFunction($functions);
    expect($function->getName())->toBe('fn5');
    expect($function->countArguments())->toBe(2);
    expect($function->getArguments())->toBe([6, 7.5]);
    expect($function->getLine())->toBe(6);
    expect($function->getLastLine())->toBe(6);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);

    //fn6
    $function = shiftPhpFunction($functions);
    expect($function->getName())->toBe('fn6');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe([['arr']]);
    expect($function->getLine())->toBe(7);
    expect($function->getLastLine())->toBe(7);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);

    //fn7
    $function = shiftPhpFunction($functions);
    expect($function->getName())->toBe('fn7');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe([null]);
    expect($function->getLine())->toBe(8);
    expect($function->getLastLine())->toBe(8);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);

    //fn9
    $function = shiftPhpFunction($functions);
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
    $function = shiftPhpFunction($functions);
    expect($function->getName())->toBe('fn10');
    expect($function->countArguments())->toBe(0);
    expect($function->getLine())->toBe(13);
    expect($function->getLastLine())->toBe(13);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(1);

    $comments = $function->getComments();
    expect(array_shift($comments))->toBe('Comment to fn10');

    //fn11
    $function = shiftPhpFunction($functions);
    expect($function->getName())->toBe('fn11');
    expect($function->countArguments())->toBe(2);
    expect($function->getArguments())->toBe(['arg9', 'arg10']);
    expect($function->getLine())->toBe(16);
    expect($function->getLastLine())->toBe(16);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(2);

    $comments = $function->getComments();
    expect(array_shift($comments))->toBe('Related comment 1');
    expect(array_shift($comments))->toBe('ALLOW: Related comment 2');

    //fn12
    $function = shiftPhpFunction($functions);
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
    $function = shiftPhpFunction($functions);
    expect($function->getName())->toBe('fn13');
    expect($function->countArguments())->toBe(3);
    expect($function->getArguments())->toBe([
        'Translatable string',
        '',
        ['context' => 'Context string', 'foo'],
    ]);
    expect($function->getLine())->toBe(30);
    expect($function->getLastLine())->toBe(30);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(1);

    $comments = $function->getComments();
    expect(array_shift($comments))->toBe('Related comment 5');

    //fn14
    $function = shiftPhpFunction($functions);
    expect($function->getName())->toBe('fn14');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe(['Translatable string']);
    expect($function->getLine())->toBe(32);
    expect($function->getLastLine())->toBe(32);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);

    //fn15
    $function = shiftPhpFunction($functions);
    expect($function->getName())->toBe('fn15');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe(['Translatable long string']);
    expect($function->getLine())->toBe(33);
    expect($function->getLastLine())->toBe(34);
    expect($function->getFilename())->toBe($file);
    expect($function->getComments())->toHaveCount(0);
});

it('handles first class callable syntax', function (): void {
    $scanner = new PhpFunctionsScanner();
    $functions = $scanner->scan('<?php $fn = __(...);', 'foo.php');

    expect($functions)->toHaveCount(1);

    $function = shiftPhpFunction($functions);
    expect($function->getName())->toBe('__');
    expect($function->countArguments())->toBe(1);
    expect($function->getArguments())->toBe([null]);
});

/**
 * Shifts a parsed function off the array, asserting its presence.
 *
 * @param array<ParsedFunction> $functions
 */
function shiftPhpFunction(array &$functions): ParsedFunction
{
    $function = $functions[0] ?? null;

    if (!$function instanceof ParsedFunction) {
        Assert::fail('No more parsed functions available');
    }

    array_shift($functions);

    return $function;
}
