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
use PHPUnit\Framework\TestCase;

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
class LoaderEdgesTest extends TestCase
{
    public function testArrayLoaderCannotLoadFromStrings(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Arrays cannot be loaded from string. Use ArrayLoader::loadFile() instead');

        (new ArrayLoader())->loadString('anything');
    }

    public function testArrayLoaderRejectsFilesNotReturningArrays(): void
    {
        $file = $this->createTempFile('<?php return "not-an-array";');

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("Invalid translations file '$file': it must return an array");

            (new ArrayLoader())->loadFile($file);
        } finally {
            unlink($file);
        }
    }

    public function testJsonLoaderRejectsInvalidPayloads(): void
    {
        $file = $this->createTempFile('"just-a-string"');

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Invalid translations file: it must contain a JSON object');

            (new JsonLoader())->loadFile($file);
        } finally {
            unlink($file);
        }
    }

    public function testMalformedDictionaryStructuresAreSkippedSafely(): void
    {
        $file = $this->createTempFile(
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

    public function testScalarContextsAndEmptyOriginalsAreSkipped(): void
    {
        $file = $this->createTempFile(
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
        $file = $this->createTempFile('whatever');
        chmod($file, 0000);

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("Cannot read the file '$file', probably permissions");

            (new MoLoader())->loadFile($file);
        } finally {
            chmod($file, 0600);
            unlink($file);
        }
    }

    private function createTempFile(string $content): string
    {
        $file = sys_get_temp_dir() . '/gettext-loader-edge-' . uniqid() . '.txt';
        file_put_contents($file, $content);

        return $file;
    }
}
