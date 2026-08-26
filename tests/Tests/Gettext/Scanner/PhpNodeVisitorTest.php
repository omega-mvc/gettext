<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Scanner;

use Omega\Gettext\Scanner\ParsedFunction;
use Omega\Gettext\Scanner\PhpFunctionsScanner;
use Omega\Gettext\Scanner\PhpNodeVisitor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParsedFunction::class)]
#[CoversClass(PhpFunctionsScanner::class)]
#[CoversClass(PhpNodeVisitor::class)]
class PhpNodeVisitorTest extends TestCase
{
    public function testCommentedCallToUnknownFunctionIsBufferedNotExtracted(): void
    {
        $scanner = new PhpFunctionsScanner(['__']);

        $functions = $scanner->scan(
            '<?php return /* translators: hi */ myHelper("x");',
            'virtual.php'
        );

        $this->assertSame([], $functions);
    }

    public function testCommentAttachedToTheCallIsCollected(): void
    {
        $scanner = new PhpFunctionsScanner(['__']);

        $functions = $scanner->scan(
            "<?php return /* translators: hi */ __('Hello');",
            'virtual.php'
        );

        $this->assertCount(1, $functions);

        $function = $functions[0];

        $this->assertSame('__', $function->getName());
        $this->assertSame(['translators: hi'], $function->getComments());
        $this->assertSame(['Hello'], $function->getStringArguments(1));
    }

    public function testDynamicArrayKeyFallsBackToNumericItem(): void
    {
        $scanner = new PhpFunctionsScanner(['__']);

        $functions = $scanner->scan(
            "<?php __(('a' . PHP_VERSION), [time() => 'v']);",
            'virtual.php'
        );

        $this->assertCount(1, $functions);
        $this->assertCount(2, $functions[0]->getArguments());
    }

    public function testMethodAndStaticCallsAreExtractedByName(): void
    {
        $scanner = new PhpFunctionsScanner(['__']);

        $functions = $scanner->scan(
            '<?php $obj->__("A"); Cls::__("B");',
            'virtual.php'
        );

        $this->assertCount(2, $functions);

        foreach ($functions as $function) {
            $this->assertSame('__', $function->getName());
        }
    }

    public function testArrayArgumentsAreReducedToLiteralMaps(): void
    {
        $scanner = new PhpFunctionsScanner(['__']);

        $functions = $scanner->scan(
            "<?php __('x', ['plain', 'k' => 'v', time() => 'd', 'ab' . 'cd']);",
            'virtual.php'
        );

        $this->assertCount(1, $functions);

        $arguments = $functions[0]->getArguments();

        $this->assertSame('x', $arguments[0]);

        $array = $arguments[1];

        $this->assertSame(['plain', 'k' => 'v', 1 => 'd', 2 => 'abcd'], $array);
    }
}
