<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use InvalidArgumentException;
use Omega\Gettext\Formatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Formatter::class)]
class FormatterTest extends TestCase
{
    public function testFormatWithoutArgumentsReturnsTextUnchanged(): void
    {
        $formatter = new Formatter();

        $this->assertSame('Hello world', $formatter->format('Hello world', []));
    }

    public function testPrintfStyleReplacement(): void
    {
        $formatter = new Formatter();

        $this->assertSame(
            'Hello John, you have 3 messages',
            $formatter->format('Hello %s, you have %d messages', ['John', 3])
        );
    }

    public function testPrintfStyleAcceptsNullArguments(): void
    {
        $formatter = new Formatter();

        $this->assertSame('a-', $formatter->format('%s-%s', ['a', null]));
    }

    public function testMapStyleReplacement(): void
    {
        $formatter = new Formatter();

        $this->assertSame(
            'Hi John, welcome to Rome',
            $formatter->format('Hi %name, welcome to %place', ['%name' => 'John', '%place' => 'Rome'])
        );
    }

    public function testMapStyleCastsScalarsToStrings(): void
    {
        $formatter = new Formatter();

        $this->assertSame(
            '1 1.5 1',
            $formatter->format('%int %float %bool', ['%int' => 1, '%float' => 1.5, '%bool' => true])
        );
    }

    public function testEmptyMapLeavesTextUnchanged(): void
    {
        $formatter = new Formatter();

        $this->assertSame('Hi %name', $formatter->format('Hi %name', [[]]));
    }

    public function testMapStyleRejectsNonScalarValues(): void
    {
        $formatter = new Formatter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter replacements must be scalars, array given');

        $formatter->format('Hi %data', ['%data' => ['nested']]);
    }

    public function testPrintfStyleRejectsNonScalarArguments(): void
    {
        $formatter = new Formatter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formatter arguments must be scalars, array given');

        $formatter->format('Hello %s and %s', ['John', ['nested']]);
    }
}
