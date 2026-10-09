<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Languages;

use Exception;
use Omega\Gettext\Languages\Category;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Tests\TestCase;

#[CoversClass(Category::class)]
final class RulesTest extends TestCase
{
    /**
     * @return array<int, array{string, string, string, list<string>, string, string}>
     */
    public static function rules(): array
    {
        $testData = [];

        foreach (['php', 'json'] as $format) {
            foreach (self::readData($format) as $locale => $info) {
                foreach ($info['examples'] as $rule => $numbers) {
                    $testData[] = [
                        $format,
                        $locale,
                        $info['formula'],
                        $info['cases'],
                        $numbers,
                        $rule,
                    ];
                }
            }
        }

        return $testData;
    }

    /**
     * @return array<int, array{string, string, string, array<string, string>}>
     */
    public static function cases(): array
    {
        $testData = [];

        foreach (['php', 'json'] as $format) {
            foreach (self::readData($format) as $locale => $info) {
                foreach ($info['cases'] as $case) {
                    $testData[] = [
                        $format,
                        $locale,
                        $case,
                        $info['examples'],
                    ];
                }
            }
        }

        return $testData;
    }

    /**
     * @param list<string> $allCases
     */
    #[DataProvider('rules')]
    public function testEvaluatesThePluralRulesAgainstTheCldrExamples(
        string $format,
        string $locale,
        string $formula,
        array $allCases,
        string $numbers,
        string $expectedCase,
    ): void {
        $expectedCaseIndex = in_array($expectedCase, $allCases, true);

        foreach (Category::expandExamples($numbers) as $number) {
            $numericFormula = preg_replace('/\bn\b/', (string) $number, $formula);

            if ($numericFormula === null) {
                throw new Exception("Failed to build the numeric formula for {$number}");
            }

            $extraneousChars = preg_replace('/^[\d %!=<>&\|()?:]+$/', '', $numericFormula);

            if ($extraneousChars === null) {
                throw new Exception("Failed to validate the numeric formula '{$numericFormula}'");
            }

            $this->assertSame(
                '',
                $extraneousChars,
                "The formula '{$numericFormula}' contains extraneous characters:"
                . " '{$extraneousChars}' (format: {$format})"
            );

            $caseIndex = @eval(
                "return (({$numericFormula}) === true) ? 1 :"
                . " ((({$numericFormula}) === false) ? 0 : ({$numericFormula}));"
            );

            $this->assertIsInt(
                $caseIndex,
                "Error evaluating the numeric formula '{$numericFormula}' (format: {$format})"
            );

            $this->assertArrayHasKey(
                $caseIndex,
                $allCases,
                "The formula '{$formula}' evaluated for {$number} gave an out-of-range case index"
                . " ({$caseIndex}) (format: {$format})"
            );

            $case = $allCases[$caseIndex] ?? null;

            $this->assertIsString($case, "The formula '{$formula}' evaluated for {$number} gave no case");

            $this->assertSame(
                $expectedCase,
                $case,
                "The formula '{$formula}' evaluated for {$number} resulted in '{$case}' ({$caseIndex}) instead"
                . " of '{$expectedCase}' (" . var_export($expectedCaseIndex, true) . ") (format: {$format})"
            );
        }
    }

    /**
     * @param array<string, string> $examples
     */
    #[DataProvider('cases')]
    public function testHasExamplesForEveryCase(string $format, string $locale, string $case, array $examples): void
    {
        $this->assertArrayHasKey($case, $examples);
    }

    /**
     * Loads and validates the CLDR test data for the given format.
     *
     * @return array<string, array{formula: string, cases: list<string>, examples: array<string, string>}>
     */
    private static function readData(string $format): array
    {
        /** @var array<string, array<string, array{formula: string, cases: list<string>, examples: array<string, string>}>> $dataCache */
        static $dataCache = [];

        if (!array_key_exists($format, $dataCache)) {
            $dataCache[$format] = self::loadData($format);
        }

        return $dataCache[$format];
    }

    /**
     * Loads and validates the CLDR test data for the given format.
     *
     * @return array<string, array{formula: string, cases: list<string>, examples: array<string, string>}>
     */
    private static function loadData(string $format): array
    {
        $filename = GETTEXT_LANGUAGES_TESTDIR . '/data.' . $format;

        $loaded = match ($format) {
            'php' => require $filename,
            'json' => json_decode((string) file_get_contents($filename), true),
            default => throw new Exception("Unhandled format: {$format}"),
        };

        if (!is_array($loaded)) {
            throw new Exception("Invalid test data for format: {$format}");
        }

        $validated = [];

        foreach ($loaded as $localeKey => $info) {
            if (!is_array($info)) {
                continue;
            }

            $formula = $info['formula'] ?? null;
            $rawCases = $info['cases'] ?? null;
            $rawExamples = $info['examples'] ?? null;

            if (!is_string($formula) || !is_array($rawCases) || !is_array($rawExamples)) {
                continue;
            }

            $cases = [];
            foreach ($rawCases as $case) {
                if (is_string($case)) {
                    $cases[] = $case;
                }
            }

            $examples = [];
            foreach ($rawExamples as $ruleKey => $numbers) {
                if (is_string($numbers)) {
                    $examples[(string) $ruleKey] = $numbers;
                }
            }

            $validated[(string) $localeKey] = [
                'formula' => $formula,
                'cases' => $cases,
                'examples' => $examples,
            ];
        }

        return $validated;
    }
}
