<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Languages;

use Omega\Gettext\Languages\Category;
use Omega\Gettext\Languages\CldrData;
use Omega\Gettext\Languages\Exporter\Php;
use Omega\Gettext\Languages\FormulaConverter;
use Omega\Gettext\Languages\Language;

covers(Category::class);
covers(CldrData::class);
covers(FormulaConverter::class);
covers(Php::class);
covers(Language::class);

it('exports only ascii characters', function (): void {
    $array = getExportedPhpArray();
    foreach ($array as $localeID => $localeData) {
        assertUSAscii((string) $localeID, $localeData);
    }
});

function assertUSAscii(string $key, mixed $value): void
{
    if (is_string($value)) {
        expect(preg_match('/^[\x20-\x7F\n]*$/s', $value))
            ->toBe(1, "The string at {$key} does not contain only US-ASCII characters: {$value}");

        return;
    }

    if (is_array($value)) {
        foreach ($value as $valueKey => $valueValue) {
            assertUSAscii("{$key}.{$valueKey}", $valueValue);
        }
    }
}

/**
 * @return array<array-key, mixed>
 */
function getExportedPhpArray(): array
{
    $phpCode = Php::toString(Language::getAll(), ['us-ascii' => true]);
    $stripped = preg_replace('/^<\?php\n/', '', $phpCode);
    $exported = is_string($stripped) ? eval($stripped) : null;

    return is_array($exported) ? $exported : [];
}
