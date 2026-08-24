<?php

declare(strict_types=1);

namespace Tests\Gettext\Languages;

use Exception;
use Gettext\Languages\FormulaConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FormulaConverter::class)]
class FormulaConverterTest extends TestCase
{
    public function testConvertFormulaWithInvalidFormula()
    {
        $this->expectException(Exception::class);
        FormulaConverter::convertFormula('()');
    }

    public function testConvertAtomWithInvalidFormulaChunk()
    {
        $this->expectException(Exception::class);
        FormulaConverter::convertFormula('f ==== empty');
    }
}
