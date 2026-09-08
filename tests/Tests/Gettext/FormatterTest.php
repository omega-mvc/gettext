<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use InvalidArgumentException;
use Omega\Gettext\Formatter;

covers(Formatter::class);

it('returns the text unchanged when no arguments are given', function (): void {
    $formatter = new Formatter();

    expect($formatter->format('Hello world', []))->toBe('Hello world');
});

it('replaces printf style placeholders', function (): void {
    $formatter = new Formatter();

    expect($formatter->format('Hello %s, you have %d messages', ['John', 3]))
        ->toBe('Hello John, you have 3 messages');
});

it('accepts null arguments in printf style', function (): void {
    $formatter = new Formatter();

    expect($formatter->format('%s-%s', ['a', null]))->toBe('a-');
});

it('replaces map style placeholders', function (): void {
    $formatter = new Formatter();

    expect($formatter->format('Hi %name, welcome to %place', ['%name' => 'John', '%place' => 'Rome']))
        ->toBe('Hi John, welcome to Rome');
});

it('casts map style scalars to strings', function (): void {
    $formatter = new Formatter();

    expect($formatter->format('%int %float %bool', ['%int' => 1, '%float' => 1.5, '%bool' => true]))
        ->toBe('1 1.5 1');
});

it('leaves the text unchanged when the map is empty', function (): void {
    $formatter = new Formatter();

    expect($formatter->format('Hi %name', [[]]))->toBe('Hi %name');
});

it('rejects non scalar map values', function (): void {
    $formatter = new Formatter();

    expect(fn () => $formatter->format('Hi %data', ['%data' => ['nested']]))
        ->toThrow(InvalidArgumentException::class, 'Formatter replacements must be scalars, array given');
});

it('rejects non scalar printf arguments', function (): void {
    $formatter = new Formatter();

    expect(fn () => $formatter->format('Hello %s and %s', ['John', ['nested']]))
        ->toThrow(InvalidArgumentException::class, 'Formatter arguments must be scalars, array given');
});
