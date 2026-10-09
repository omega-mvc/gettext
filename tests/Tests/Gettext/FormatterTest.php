<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use InvalidArgumentException;
use Omega\Gettext\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Tests\TestCase;

#[CoversClass(Formatter::class)]
final class FormatterTest extends TestCase
{
    public function testReturnsTheTextUnchangedWhenNoArgumentsAreGiven(): void
    {
        $formatter = new Formatter();

        $this->assertSame('Hello world', $formatter->format('Hello world', []));
    }

    public function testReplacesPrintfStylePlaceholders(): void
    {
        $formatter = new Formatter();

        $this->assertSame(
            'Hello John, you have 3 messages',
            $formatter->format('Hello %s, you have %d messages', ['John', 3])
        );
    }

    public function testAcceptsNullArgumentsInPrintfStyle(): void
    {
        $formatter = new Formatter();

        $this->assertSame('a-', $formatter->format('%s-%s', ['a', null]));
    }

    public function testReplacesMapStylePlaceholders(): void
    {
        $formatter = new Formatter();

        $this->assertSame(
            'Hi John, welcome to Rome',
            $formatter->format('Hi %name, welcome to %place', ['%name' => 'John', '%place' => 'Rome'])
        );
    }

    public function testCastsMapStyleScalarsToStrings(): void
    {
        $formatter = new Formatter();

        $this->assertSame(
            '1 1.5 1',
            $formatter->format('%int %float %bool', ['%int' => 1, '%float' => 1.5, '%bool' => true])
        );
    }

    public function testLeavesTheTextUnchangedWhenTheMapIsEmpty(): void
    {
        $formatter = new Formatter();

        $this->assertSame('Hi %name', $formatter->format('Hi %name', [[]]));
    }

    public function testRejectsNonScalarMapValues(): void
    {
        $formatter = new Formatter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter replacements must be scalars, array given');

        $formatter->format('Hi %data', ['%data' => ['nested']]);
    }

    public function testRejectsNonScalarPrintfArguments(): void
    {
        $formatter = new Formatter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter arguments must be scalars, array given');

        $formatter->format('Hello %s and %s', ['John', ['nested']]);
    }
}
