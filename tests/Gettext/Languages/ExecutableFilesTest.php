<?php

declare(strict_types=1);

namespace Tests\Gettext\Languages;

use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class ExecutableFilesTest extends TestCase
{
    public function testExecutableFiles()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Testing executable files requires a Posix environment');
        }
        $expected = [
            'bin/export-plural-rules',
            'bin/import-cldr-data',
        ];
        $actual = $this->listExecutableFiles();
        $this->assertSame($expected, $actual);
    }

    /**
     * @return string[]
     */
    private function listExecutableFiles(): array
    {
        $rc = -1;
        $output = [];
        exec('find ' . escapeshellarg(GETTEXT_LANGUAGES_TESTROOTDIR) . ' -type f -executable 2>&1', $output, $rc);
        if ($rc !== 0) {
            $this->markTestSkipped('Failed to retrieve the list of executable files (' . trim(implode("\n", $output)) . ')');
        }
        $result = array_map(
            function ($file) {
                return substr($file, strlen(GETTEXT_LANGUAGES_TESTROOTDIR) + 1);
            },
            $output
        );
        $result = array_filter(
            $result,
            function ($file) {
                return $file !== '' && !str_starts_with($file, '.git/') && !str_starts_with($file, 'vendor/');
            }
        );
        sort($result);

        return $result;
    }
}
