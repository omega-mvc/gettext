<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Languages;

use Exception;
use Omega\Gettext\Languages\Category;
use Omega\Gettext\Languages\FormulaConverter;

covers(Category::class);
covers(FormulaConverter::class);

it('rejects a constructor with an invalid cldr id', function (): void {
    expect(fn () => new Category('invalid-cldr-category', 'i = 1 and v = 0 @integer 1'))
        ->toThrow(Exception::class);
});

it('rejects a constructor whose cldr id is not in the list', function (): void {
    expect(fn () => new Category('pluralRule-count-10000000', 'i = 1 and v = 0 @integer 1'))
        ->toThrow(Exception::class);
});

it('rejects a constructor with an invalid cldr rule', function (): void {
    expect(fn () => new Category('pluralRule-count-one', 'invalid category rule'))
        ->toThrow(Exception::class);
});

it('returns the example integers', function (): void {
    $category = new Category('pluralRule-count-one', 'i = 1 and v = 0 @integer 1');

    expect($category->getExampleIntegers())->toBe([1]);
});
