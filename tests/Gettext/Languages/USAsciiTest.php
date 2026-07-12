<?php

declare(strict_types=1);

namespace Tests\Gettext\Languages;

use Gettext\Languages\Category;
use Gettext\Languages\CldrData;
use Gettext\Languages\Exporter\Php;
use Gettext\Languages\FormulaConverter;
use Gettext\Languages\Language;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Category::class)]
#[CoversClass(CldrData::class)]
#[CoversClass(FormulaConverter::class)]
#[CoversClass(Php::class)]
#[CoversClass(Language::class)]
class USAsciiTest extends TestCase
{
    public function testExportUSAscii()
    {
        $array = $this->getExportedPhpArray();
        foreach ($array as $localeID => $localeData) {
            $this->assertUSAscii($localeID, $localeData);
        }
    }

    /**
     * @param string $key
     */
    private function assertUSAscii($key, $value)
    {
        switch (gettype($value)) {
            case 'string':
                $this->assertSame(1, preg_match('/^[\x20-\x7F\n]*$/s', $value), "The string at {$key} does not contain only US-ASCII characters: {$value}");
                break;
            case 'array':
                foreach ($value as $valueKey => $valueValue) {
                    $this->assertUSAscii("{$key}.{$valueKey}", $valueValue);
                }
                break;
        }
    }

    /**
     * @return array
     */
    private function getExportedPhpArray()
    {
        $phpCode = Php::toString(Language::getAll(), array('us-ascii' => true));

        return eval(preg_replace('/^<\?php\n/', '', $phpCode));
    }
}
