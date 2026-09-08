<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use LogicException;
use Omega\Gettext\Formatter;
use Omega\Gettext\Translator;
use Omega\Gettext\TranslatorFunctions;
use ReflectionClass;

covers(Formatter::class);
covers(Translator::class);
covers(TranslatorFunctions::class);

beforeEach(function (): void {
    $reflection = new ReflectionClass(TranslatorFunctions::class);

    foreach (['translator', 'formatter'] as $propertyName) {
        $property = $reflection->getProperty($propertyName);
        $property->setValue(null, null);
    }
});

it('throws before a translator is registered', function (): void {
    expect(fn () => TranslatorFunctions::getTranslator())
        ->toThrow(LogicException::class, 'No translator registered, call TranslatorFunctions::register() first');
});

it('throws before a formatter is registered', function (): void {
    expect(fn () => TranslatorFunctions::getFormatter())
        ->toThrow(LogicException::class, 'No formatter registered, call TranslatorFunctions::register() first');
});

it('stores the provided instances when registering', function (): void {
    $translator = new Translator();
    $formatter = new Formatter();

    TranslatorFunctions::register($translator, $formatter);

    expect(TranslatorFunctions::getTranslator())->toBe($translator);
    expect(TranslatorFunctions::getFormatter())->toBe($formatter);
});

it('creates a default formatter when registering without one', function (): void {
    $translator = new Translator();

    TranslatorFunctions::register($translator);

    expect(TranslatorFunctions::getTranslator())->toBe($translator);
    expect(TranslatorFunctions::getFormatter())->toBeInstanceOf(Formatter::class);
});

it('replaces previously registered instances', function (): void {
    $first = new Translator();
    $second = new Translator();
    $formatter = new Formatter();

    TranslatorFunctions::register($first);
    TranslatorFunctions::register($second, $formatter);

    expect(TranslatorFunctions::getTranslator())->toBe($second);
    expect(TranslatorFunctions::getFormatter())->toBe($formatter);
});
