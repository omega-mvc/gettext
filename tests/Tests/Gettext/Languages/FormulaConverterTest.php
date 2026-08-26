<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Languages;

use Exception;
use Omega\Gettext\Languages\FormulaConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FormulaConverter::class)]
class FormulaConverterTest extends TestCase
{
    public function testConvertFormulaWithInvalidFormula(): void
    {
        $this->expectException(Exception::class);
        FormulaConverter::convertFormula('()');
    }

    public function testConvertAtomWithInvalidFormulaChunk(): void
    {
        $this->expectException(Exception::class);
        FormulaConverter::convertFormula('f ==== empty');
    }
}
