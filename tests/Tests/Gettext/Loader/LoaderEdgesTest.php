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

covers(ArrayLoader::class);
covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(JsonLoader::class);
covers(Loader::class);
covers(MoLoader::class);
covers(References::class);
covers(Translation::class);
covers(Translations::class);

it('refuses to load arrays from strings', function (): void {
    expect(fn () => (new ArrayLoader())->loadString('anything'))->toThrow(
        BadMethodCallException::class,
        'Arrays cannot be loaded from string. Use ArrayLoader::loadFile() instead'
    );
});

it('rejects files that do not return arrays', function (): void {
    $file = createTempFile('<?php return "not-an-array";');

    try {
        expect(fn () => (new ArrayLoader())->loadFile($file))
            ->toThrow(Exception::class, "Invalid translations file '$file': it must return an array");
    } finally {
        unlink($file);
    }
});

it('rejects invalid json payloads', function (): void {
    $file = createTempFile('"just-a-string"');

    try {
        expect(fn () => (new JsonLoader())->loadFile($file))
            ->toThrow(Exception::class, 'Invalid translations file: it must contain a JSON object');
    } finally {
        unlink($file);
    }
});

it('skips malformed dictionary structures safely', function (): void {
    $file = createTempFile(
        '<?php return '
        . var_export([
            'messages' => 'not-an-array',
        ], true) . ';'
    );

    try {
        $translations = (new ArrayLoader())->loadFile($file);

        expect($translations)->toHaveCount(0);
    } finally {
        unlink($file);
    }
});

it('skips scalar contexts and empty originals', function (): void {
    $file = createTempFile(
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

        expect($translations)->toHaveCount(1);
        expect($translations->find(null, 'real'))->not->toBeNull();
        expect($translations->find(null, ''))->toBeNull();
    } finally {
        unlink($file);
    }
});

it('throws for unreadable files', function (): void {
    $file = createTempFile('whatever');
    chmod($file, 0000);

    try {
        expect(fn () => (new MoLoader())->loadFile($file))
            ->toThrow(Exception::class, "Cannot read the file '$file', probably permissions");
    } finally {
        chmod($file, 0600);
        unlink($file);
    }
});

function createTempFile(string $content): string
{
    $file = sys_get_temp_dir() . '/gettext-loader-edge-' . uniqid() . '.txt';
    file_put_contents($file, $content);

    return $file;
}
