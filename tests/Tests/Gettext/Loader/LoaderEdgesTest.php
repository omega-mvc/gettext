<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Loader;

use BadMethodCallException;
use Exception;
use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\Loader\ArrayLoader;
use Omega\Gettext\Loader\JsonLoader;
use Omega\Gettext\Loader\Loader;
use Omega\Gettext\Loader\MoLoader;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Tests\TestCase;

#[CoversClass(ArrayLoader::class)]
#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(JsonLoader::class)]
#[CoversClass(Loader::class)]
#[CoversClass(MoLoader::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
final class LoaderEdgesTest extends TestCase
{
    public function testRefusesToLoadArraysFromStrings(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Arrays cannot be loaded from string. Use ArrayLoader::loadFile() instead');

        (new ArrayLoader())->loadString('anything');
    }

    public function testRejectsFilesThatDoNotReturnArrays(): void
    {
        $file = self::createTempFile('<?php return "not-an-array";');

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("Invalid translations file '$file': it must return an array");

            (new ArrayLoader())->loadFile($file);
        } finally {
            unlink($file);
        }
    }

    public function testRejectsInvalidJsonPayloads(): void
    {
        $file = self::createTempFile('"just-a-string"');

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Invalid translations file: it must contain a JSON object');

            (new JsonLoader())->loadFile($file);
        } finally {
            unlink($file);
        }
    }

    public function testSkipsMalformedDictionaryStructuresSafely(): void
    {
        $file = self::createTempFile(
            '<?php return '
            . var_export([
                'messages' => 'not-an-array',
            ], true) . ';'
        );

        try {
            $translations = (new ArrayLoader())->loadFile($file);

            $this->assertCount(0, $translations);
        } finally {
            unlink($file);
        }
    }

    public function testSkipsScalarContextsAndEmptyOriginals(): void
    {
        $file = self::createTempFile(
            '<?php return '
            . var_export([
                'domain' => 'mixed',
                'messages' => [
                    'scalar-context' => 'dropped',
                    '' => [
                        '' => 'skipped-empty-original',
                        'real' => 'KEPT',
                    ],
                ],
            ], true) . ';'
        );

        try {
            $translations = (new ArrayLoader())->loadFile($file);

            $this->assertCount(1, $translations);
            $this->assertNotNull($translations->find(null, 'real'));
            $this->assertNull($translations->find(null, ''));
        } finally {
            unlink($file);
        }
    }

    public function testUnreadableFilesThrow(): void
    {
        $file = self::createTempFile('whatever');
        chmod($file, 0000);

        set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline): bool {
            return true;
        });

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("Cannot read the file '$file', probably permissions");

            (new MoLoader())->loadFile($file);
        } finally {
            restore_error_handler();
            chmod($file, 0600);
            unlink($file);
        }
    }

    private static function createTempFile(string $content): string
    {
        $file = sys_get_temp_dir() . '/gettext-loader-edge-' . uniqid() . '.txt';
        file_put_contents($file, $content);

        return $file;
    }
}
