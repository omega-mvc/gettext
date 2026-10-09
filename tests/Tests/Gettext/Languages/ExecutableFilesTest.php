<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Languages;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\Tests\TestCase;

#[CoversNothing]
final class ExecutableFilesTest extends TestCase
{
    public function testKeepsTheRepoScriptsExecutable(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Testing executable files requires a Posix environment');
        }

        $expected = [
            'bin/export-plural-rules',
            'bin/import-cldr-data',
        ];

        $this->assertSame($expected, self::listExecutableFiles());
    }

    /**
     * @return string[]
     */
    private static function listExecutableFiles(): array
    {
        $rc = -1;
        $output = [];
        exec('find ' . escapeshellarg(GETTEXT_LANGUAGES_TESTROOTDIR) . ' -type f -executable 2>&1', $output, $rc);

        if ($rc !== 0) {
            Assert::markTestSkipped(
                'Failed to retrieve the list of executable files (' . trim(implode("\n", $output)) . ')'
            );
        }

        $result = array_map(
            function (string $file): string {
                return substr($file, strlen(GETTEXT_LANGUAGES_TESTROOTDIR) + 1);
            },
            $output
        );
        $result = array_filter(
            $result,
            function (string $file): bool {
                return $file !== '' && !str_starts_with($file, '.git/') && !str_starts_with($file, 'vendor/');
            }
        );
        sort($result);

        return $result;
    }
}
