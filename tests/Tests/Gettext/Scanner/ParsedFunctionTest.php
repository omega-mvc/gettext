<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Scanner;

use Closure;
use Omega\Gettext\Scanner\ParsedFunction;

covers(ParsedFunction::class);

it('defaults the last line to the current line', function (): void {
    $function = new ParsedFunction('__', 'file.php', 7);

    expect($function->getName())->toBe('__');
    expect($function->getFilename())->toBe('file.php');
    expect($function->getLine())->toBe(7);
    expect($function->getLastLine())->toBe(7);
});

it('keeps an explicit last line', function (): void {
    $function = new ParsedFunction('ngettext', 'file.php', 3, 9);

    expect($function->getLine())->toBe(3);
    expect($function->getLastLine())->toBe(9);
});

it('exposes the full state to array and debug info', function (): void {
    $function = new ParsedFunction('pgettext', 'app.js', 2, 4);
    $function->addArgument('context');
    $function->addArgument();
    $function->addComment('translators: greeting');
    $function->addFlag('js-format');

    $expected = [
        'name' => 'pgettext',
        'filename' => 'app.js',
        'line' => 2,
        'lastLine' => 4,
        'arguments' => ['context', null],
        'comments' => ['translators: greeting'],
        'flags' => ['js-format'],
    ];

    expect($function->toArray())->toBe($expected);

    $debugInfo = Closure::bind(
        static fn (): array => $function->__debugInfo(),
        null,
        ParsedFunction::class
    )();

    expect($debugInfo)->toBe($expected);
});

it('counts arguments and filters them by type', function (): void {
    $function = new ParsedFunction('dgettext', 'f.js', 1);
    $function->addArgument('domain');
    $function->addArgument(123);
    $function->addArgument(null);

    expect($function->getArguments())->toHaveCount(3);
    expect($function->countArguments())->toBe(3);
    expect($function->getStringArguments(3))->toBe(['domain']);
    expect($function->getStringArguments(0))->toBe([]);
});
