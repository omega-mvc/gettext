<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Languages;

use Exception;
use Omega\Gettext\Languages\FormulaConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Tests\TestCase;

#[CoversClass(FormulaConverter::class)]
final class FormulaConverterTest extends TestCase
{
    public function testRejectsAnInvalidFormula(): void
    {
        $this->expectException(Exception::class);

        FormulaConverter::convertFormula('()');
    }

    public function testRejectsAnInvalidAtomChunk(): void
    {
        $this->expectException(Exception::class);

        FormulaConverter::convertFormula('f ==== empty');
    }
}
