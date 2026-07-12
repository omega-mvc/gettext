<?php

declare(strict_types=1);

namespace Tests\Gettext\Languages;

use Exception;
use Gettext\Languages\Category;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Category::class)]
class RulesTest extends TestCase
{
    public static function providerTestRules()
    {
        $testData = array();
        foreach (array('php', 'json') as $format) {
            foreach (static::readData($format) as $locale => $info) {
                foreach ($info['examples'] as $rule => $numbers) {
                    $testData[] = array(
                        $format,
                        $locale,
                        $info['formula'],
                        $info['cases'],
                        $numbers,
                        $rule,
                    );
                }
            }
        }

        return $testData;
    }

    #[DataProvider('providerTestRules')]
    public function testRules($format, $locale, $formula, $allCases, $numbers, $expectedCase)
    {
        $expectedCaseIndex = in_array($expectedCase, $allCases);
        foreach (Category::expandExamples($numbers) as $number) {
            $numericFormula = preg_replace('/\bn\b/', (string) $number, $formula);
            $extraneousChars = preg_replace('/^[\d %!=<>&\|()?:]+$/', '', $numericFormula);
            $this->assertSame('', $extraneousChars, "The formula '{$numericFormula}' contains extraneous characters: '{$extraneousChars}' (format: {$format})");

            $caseIndex = @eval("return (({$numericFormula}) === true) ? 1 : ((({$numericFormula}) === false) ? 0 : ({$numericFormula}));");
            $caseIndexType = gettype($caseIndex);
            $this->assertSame('integer', $caseIndexType, "Error evaluating the numeric formula '{$numericFormula}' (format: {$format})");

            $this->assertArrayHasKey($caseIndex, $allCases, "The formula '{$formula}' evaluated for {$number} gave an out-of-range case index ({$caseIndex}) (format: {$format})");

            $case = $allCases[$caseIndex];
            $this->assertSame($expectedCase, $case, "The formula '{$formula}' evaluated for {$number} resulted in '{$case}' ({$caseIndex}) instead of '{$expectedCase}' ({$expectedCaseIndex}) (format: {$format})");
        }
    }

    public static function providerTestExamplesExist()
    {
        $testData = array();
        foreach (array('php', 'json') as $format) {
            foreach (static::readData($format) as $locale => $info) {
                foreach ($info['cases'] as $case) {
                    $testData[] = array(
                        $format,
                        $locale,
                        $case,
                        $info['examples'],
                    );
                }
            }
        }

        return $testData;
    }

    #[DataProvider('providerTestExamplesExist')]
    public function testExamplesExist($format, $locale, $case, $examples)
    {
        $this->assertArrayHasKey($case, $examples, "The language '{$locale}' does not have tests for the case '{$case}' (format: {$format})");
    }

    private static function readData($format)
    {
        static $data = array();
        if (!isset($data[$format])) {
            $filename = GETTEXT_LANGUAGES_TESTDIR . '/data.' . $format;
            $data[$format] = match ($format) {
                'php' => require $filename,
                'json' => json_decode(file_get_contents($filename), true),
                default => throw new Exception("Unhandled format: {$format}"),
            };
        }

        return $data[$format];
    }
}
