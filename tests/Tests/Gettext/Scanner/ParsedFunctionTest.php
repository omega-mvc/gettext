<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Scanner;

use Omega\Gettext\Scanner\ParsedFunction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Closure;

#[CoversClass(ParsedFunction::class)]
class ParsedFunctionTest extends TestCase
{
    public function testConstructorDefaultsLastLineToLine(): void
    {
        $function = new ParsedFunction('__', 'file.php', 7);

        $this->assertSame('__', $function->getName());
        $this->assertSame('file.php', $function->getFilename());
        $this->assertSame(7, $function->getLine());
        $this->assertSame(7, $function->getLastLine());
    }

    public function testExplicitLastLineIsKept(): void
    {
        $function = new ParsedFunction('ngettext', 'file.php', 3, 9);

        $this->assertSame(3, $function->getLine());
        $this->assertSame(9, $function->getLastLine());
    }

    public function testToArrayAndDebugInfoExposeTheFullState(): void
    {
        $function = new ParsedFunction('pgettext', 'app.js', 2, 4);
        $function->addArgument('context');
        $function->addArgument();
        $function->addComment('translators: greeting');
        $function->addFlag('js-format');

        $expected = [
            'name' => 'pgettext',
            'filename' => 'app.js',
            'line' => 2,
            'lastLine' => 4,
            'arguments' => ['context', null],
            'comments' => ['translators: greeting'],
            'flags' => ['js-format'],
        ];

        $this->assertSame($expected, $function->toArray());

        $debugInfo = Closure::bind(
            static fn (): array => $function->__debugInfo(),
            null,
            ParsedFunction::class
        )();

        $this->assertSame($expected, $debugInfo);
    }

    public function testArgumentsCountAndStringFiltering(): void
    {
        $function = new ParsedFunction('dgettext', 'f.js', 1);
        $function->addArgument('domain');
        $function->addArgument(123);
        $function->addArgument(null);

        $this->assertCount(3, $function->getArguments());
        $this->assertSame(3, $function->countArguments());
        $this->assertSame(['domain'], $function->getStringArguments(3));
        $this->assertSame([], $function->getStringArguments(0));
    }
}
