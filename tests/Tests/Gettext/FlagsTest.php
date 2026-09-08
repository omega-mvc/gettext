<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Omega\Gettext\Flags;

covers(Flags::class);

it('manages flags', function (): void {
    $flags = new Flags();

    expect($flags->toArray())->toBe([]);
    expect($flags)->toHaveCount(0);

    $flags->add('foo');

    expect($flags->toArray())->toBe(['foo']);
    expect($flags)->toHaveCount(1);

    $flags->add('foo');

    expect($flags->toArray())->toBe(['foo']);
    expect($flags)->toHaveCount(1);

    $flags->add('bar');

    expect($flags->toArray())->toBe(['bar', 'foo']);
    expect($flags)->toHaveCount(2);

    $flags->add('one', 'two', 'three');

    expect($flags->toArray())->toBe(['bar', 'foo', 'one', 'three', 'two']);
    expect($flags)->toHaveCount(5);

    $flags->delete('bar', 'one', 'two');

    expect($flags->toArray())->toBe(['foo', 'three']);
    expect($flags)->toHaveCount(2);
});

it('merges flags', function (): void {
    $flags1 = new Flags('one', 'two', 'three');
    $flags2 = new Flags('three', 'four', 'five');

    $merged = $flags1->mergeWith($flags2);

    expect($merged)->toHaveCount(5);
    expect($merged->toArray())->toBe([
        'five',
        'four',
        'one',
        'three',
        'two',
    ]);

    expect($merged)->not->toBe($flags1);
    expect($merged)->not->toBe($flags2);
});

it('creates a flags collection from state', function (): void {
    $state = ['flags' => ['one', 'two']];
    $flags = Flags::__set_state($state);

    expect($flags)->toHaveCount(2);
    expect($flags->toArray())->toBe($state['flags']);
});
