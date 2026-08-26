<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Languages;

use Omega\Gettext\Languages\Category;
use Omega\Gettext\Languages\CldrData;
use Omega\Gettext\Languages\Exporter\Php;
use Omega\Gettext\Languages\FormulaConverter;
use Omega\Gettext\Languages\Language;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Category::class)]
#[CoversClass(CldrData::class)]
#[CoversClass(FormulaConverter::class)]
#[CoversClass(Php::class)]
#[CoversClass(Language::class)]
class USAsciiTest extends TestCase
{
    public function testExportUSAscii(): void
    {
        $array = $this->getExportedPhpArray();
        foreach ($array as $localeID => $localeData) {
            $this->assertUSAscii((string) $localeID, $localeData);
        }
    }

    private function assertUSAscii(string $key, mixed $value): void
    {
        if (is_string($value)) {
            $this->assertSame(
                1,
                preg_match('/^[\x20-\x7F\n]*$/s', $value),
                "The string at {$key} does not contain only US-ASCII characters: {$value}"
            );

            return;
        }

        if (is_array($value)) {
            foreach ($value as $valueKey => $valueValue) {
                $this->assertUSAscii("{$key}.{$valueKey}", $valueValue);
            }
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private function getExportedPhpArray(): array
    {
        $phpCode = Php::toString(Language::getAll(), array('us-ascii' => true));
        $stripped = preg_replace('/^<\?php\n/', '', $phpCode);
        $exported = is_string($stripped) ? eval($stripped) : null;

        return is_array($exported) ? $exported : [];
    }
}
