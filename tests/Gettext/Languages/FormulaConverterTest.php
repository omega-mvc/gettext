<?php

declare(strict_types=1);

namespace Tests\Gettext\Languages;

use Gettext\Languages\FormulaConverter;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FormulaConverter::class)]
class FormulaConverterTest extends TestCase
{
    public function testConvertFormulaWithInvalidFormula()
    {
        $this->isGoingToThrowException('\Exception');
        FormulaConverter::convertFormula('()');
    }

    public function testConvertAtomWithInvalidFormulaChunk()
    {
        $this->isGoingToThrowException('\Exception');
        FormulaConverter::convertFormula('f ==== empty');
    }
}
