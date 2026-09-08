<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Languages;

use Exception;
use Omega\Gettext\Languages\FormulaConverter;

covers(FormulaConverter::class);

it('rejects an invalid formula', function (): void {
    expect(fn () => FormulaConverter::convertFormula('()'))
        ->toThrow(Exception::class);
});

it('rejects an invalid atom chunk', function (): void {
    expect(fn () => FormulaConverter::convertFormula('f ==== empty'))
        ->toThrow(Exception::class);
});
