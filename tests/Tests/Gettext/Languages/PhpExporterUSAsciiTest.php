<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Languages;

use Omega\Gettext\Languages\Category;
use Omega\Gettext\Languages\CldrData;
use Omega\Gettext\Languages\Exporter\Php;
use Omega\Gettext\Languages\FormulaConverter;
use Omega\Gettext\Languages\Language;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Tests\TestCase;

#[CoversClass(Category::class)]
#[CoversClass(CldrData::class)]
#[CoversClass(FormulaConverter::class)]
#[CoversClass(Php::class)]
#[CoversClass(Language::class)]
final class PhpExporterUSAsciiTest extends TestCase
{
    public function testExportsOnlyAsciiCharacters(): void
    {
        $array = self::getExportedPhpArray();
        foreach ($array as $localeID => $localeData) {
            self::assertUSAscii((string) $localeID, $localeData);
        }
    }

    private static function assertUSAscii(string $key, mixed $value): void
    {
        if (is_string($value)) {
            Assert::assertSame(
                1,
                preg_match('/^[\x20-\x7F\n]*$/s', $value),
                "The string at {$key} does not contain only US-ASCII characters: {$value}"
            );

            return;
        }

        if (is_array($value)) {
            foreach ($value as $valueKey => $valueValue) {
                self::assertUSAscii("{$key}.{$valueKey}", $valueValue);
            }
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function getExportedPhpArray(): array
    {
        $phpCode = Php::toString(Language::getAll(), ['us-ascii' => true]);
        $stripped = preg_replace('/^<\?php\n/', '', $phpCode);
        $exported = is_string($stripped) ? eval($stripped) : null;

        return is_array($exported) ? $exported : [];
    }
}
