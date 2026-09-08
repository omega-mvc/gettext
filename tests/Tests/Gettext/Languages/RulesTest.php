<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Languages;

use Exception;
use Omega\Gettext\Languages\Category;

covers(Category::class);

dataset('rules', function (): array {
    $testData = [];

    foreach (['php', 'json'] as $format) {
        foreach (readData($format) as $locale => $info) {
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
});

dataset('cases', function (): array {
    $testData = [];

    foreach (['php', 'json'] as $format) {
        foreach (readData($format) as $locale => $info) {
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
});

/**
 * @param list<string> $allCases
 */
it(
    'evaluates the plural rules against the cldr examples',
    function (
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

            expect($extraneousChars)->toBe(
                '',
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

            expect($case)->toBe(
                $expectedCase,
                "The formula '{$formula}' evaluated for {$number} resulted in '{$case}' ({$caseIndex}) instead"
                . " of '{$expectedCase}' (" . var_export($expectedCaseIndex, true) . ") (format: {$format})"
            );
        }
    }
)->with('rules');

/**
 * @param array<string, string> $examples
 */
it(
    'has examples for every case',
    function (string $format, string $locale, string $case, array $examples): void {
        expect($examples)->toHaveKey($case);
    }
)->with('cases');

/**
 * Loads and validates the CLDR test data for the given format.
 *
 * @return array<string, array{formula: string, cases: list<string>, examples: array<string, string>}>
 */
function readData(string $format): array
{
    /** @var array<string, array<string, array{formula: string, cases: list<string>, examples: array<string, string>}>> $dataCache */
    static $dataCache = [];

    if (!array_key_exists($format, $dataCache)) {
        $dataCache[$format] = loadData($format);
    }

    return $dataCache[$format];
}

/**
 * Loads and validates the CLDR test data for the given format.
 *
 * @return array<string, array{formula: string, cases: list<string>, examples: array<string, string>}>
 */
function loadData(string $format): array
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
