<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Scanner;

use Omega\Gettext\Scanner\ParsedFunction;
use Omega\Gettext\Scanner\PhpFunctionsScanner;
use Omega\Gettext\Scanner\PhpNodeVisitor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParsedFunction::class)]
#[CoversClass(PhpNodeVisitor::class)]
#[CoversClass(PhpFunctionsScanner::class)]
class PhpFunctionsScannerTest extends TestCase
{
    /**
     * Shifts a parsed function off the array, asserting its presence.
     *
     * @param array<ParsedFunction> $functions
     */
    private function shiftFunction(array &$functions): ParsedFunction
    {
        $function = $functions[0] ?? null;

        if (!$function instanceof ParsedFunction) {
            $this->fail('No more parsed functions available');
        }

        array_shift($functions);

        return $function;
    }

    public function testScanOnEmptyCode(): void
    {
        $scanner = new PhpFunctionsScanner();
        $file = __DIR__ . '/../assets/functions.php';
        $functions = $scanner->scan('', $file);

        $this->assertSame([], $functions);
    }

    public function testPhpFunctionsExtractor(): void
    {
        $scanner = new PhpFunctionsScanner();
        $file = __DIR__ . '/../assets/functions.php';
        $code = file_get_contents($file);
        $this->assertNotFalse($code);
        $functions = $scanner->scan($code, $file);

        $this->assertCount(14, $functions);

        //fn1
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn1', $function->getName());
        $this->assertSame(3, $function->countArguments());
        $this->assertSame(['arg1', 'arg2', 3], $function->getArguments());
        $this->assertSame(4, $function->getLine());
        $this->assertSame(4, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(1, $function->getComments());

        $comments = $function->getComments();
        $this->assertSame('This comment is related with the first function', array_shift($comments));

        //fn2
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn2', $function->getName());
        $this->assertSame(1, $function->countArguments());
        $this->assertSame(5, $function->getLine());
        $this->assertSame(5, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(0, $function->getComments());

        //fn3
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn3', $function->getName());
        $this->assertSame(3, $function->countArguments());
        $this->assertSame([null, 'arg5', null], $function->getArguments());
        $this->assertSame(6, $function->getLine());
        $this->assertSame(6, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(0, $function->getComments());

        //fn4
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn4', $function->getName());
        $this->assertSame(1, $function->countArguments());
        $this->assertSame(['arg4'], $function->getArguments());
        $this->assertSame(6, $function->getLine());
        $this->assertSame(6, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(0, $function->getComments());

        //fn5
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn5', $function->getName());
        $this->assertSame(2, $function->countArguments());
        $this->assertSame([6, 7.5], $function->getArguments());
        $this->assertSame(6, $function->getLine());
        $this->assertSame(6, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(0, $function->getComments());

        //fn6
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn6', $function->getName());
        $this->assertSame(1, $function->countArguments());
        $this->assertSame([['arr']], $function->getArguments());
        $this->assertSame(7, $function->getLine());
        $this->assertSame(7, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(0, $function->getComments());

        //fn7
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn7', $function->getName());
        $this->assertSame(1, $function->countArguments());
        $this->assertSame([null], $function->getArguments());
        $this->assertSame(8, $function->getLine());
        $this->assertSame(8, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(0, $function->getComments());

        //fn9
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn9', $function->getName());
        $this->assertSame(1, $function->countArguments());
        $this->assertSame([null], $function->getArguments());
        $this->assertSame(11, $function->getLine());
        $this->assertSame(11, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(2, $function->getComments());

        $comments = $function->getComments();
        $this->assertSame('fn_8();', array_shift($comments));
        $this->assertSame('ALLOW: This is a comment to fn9', array_shift($comments));

        //fn10
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn10', $function->getName());
        $this->assertSame(0, $function->countArguments());
        $this->assertSame(13, $function->getLine());
        $this->assertSame(13, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(1, $function->getComments());

        $comments = $function->getComments();
        $this->assertSame('Comment to fn10', array_shift($comments));

        //fn11
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn11', $function->getName());
        $this->assertSame(2, $function->countArguments());
        $this->assertSame(['arg9', 'arg10'], $function->getArguments());
        $this->assertSame(16, $function->getLine());
        $this->assertSame(16, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(2, $function->getComments());

        $comments = $function->getComments();
        $this->assertSame('Related comment 1', array_shift($comments));
        $this->assertSame('ALLOW: Related comment 2', array_shift($comments));

        //fn12
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn12', $function->getName());
        $this->assertSame(2, $function->countArguments());
        $this->assertSame(['arg11', 'arg12'], $function->getArguments());
        $this->assertSame(22, $function->getLine());
        $this->assertSame(28, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(3, $function->getComments());

        $comments = $function->getComments();
        $this->assertSame("Related comment\nnumber one", array_shift($comments));
        $this->assertSame('Related comment 2', array_shift($comments));
        $this->assertSame('ALLOW: Related comment 3', array_shift($comments));

        //fn13
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn13', $function->getName());
        $this->assertSame(3, $function->countArguments());
        $this->assertSame([
            'Translatable string',
            '',
            ['context' => 'Context string', 'foo'],
        ], $function->getArguments());
        $this->assertSame(30, $function->getLine());
        $this->assertSame(30, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(1, $function->getComments());
        $comments = $function->getComments();
        $this->assertSame('Related comment 5', array_shift($comments));

        //fn14
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn14', $function->getName());
        $this->assertSame(1, $function->countArguments());
        $this->assertSame(['Translatable string'], $function->getArguments());
        $this->assertSame(32, $function->getLine());
        $this->assertSame(32, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(0, $function->getComments());

        //fn15
        $function = $this->shiftFunction($functions);
        $this->assertSame('fn15', $function->getName());
        $this->assertSame(1, $function->countArguments());
        $this->assertSame(['Translatable long string'], $function->getArguments());
        $this->assertSame(33, $function->getLine());
        $this->assertSame(34, $function->getLastLine());
        $this->assertSame($file, $function->getFilename());
        $this->assertCount(0, $function->getComments());
    }

    public function testFirstClassCallableSyntax(): void
    {
        $scanner = new PhpFunctionsScanner();
        $functions = $scanner->scan("<?php \$fn = __(...);", 'foo.php');

        $this->assertCount(1, $functions);

        $function = $this->shiftFunction($functions);
        $this->assertSame('__', $function->getName());
        $this->assertSame(1, $function->countArguments());
        $this->assertSame([null], $function->getArguments());
    }
}
