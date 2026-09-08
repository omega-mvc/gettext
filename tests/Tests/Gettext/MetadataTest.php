<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Closure;
use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\References;

covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(References::class);

it('treats flags as iterable and json serializable', function (): void {
    $flags = new Flags('b', 'a');

    $values = [];

    foreach ($flags as $flag) {
        $values[] = $flag;
    }

    expect($values)->toBe(['a', 'b']);
    expect(json_decode((string) json_encode($flags), true))->toBe(['a', 'b']);
});

it('exposes collections as arrays through debug info', function (): void {
    expect(debugInfo(new Flags('x')))->toBe(['x']);
    expect(debugInfo(new Comments('note')))->toBe(['note']);
    expect(json_decode((string) json_encode(new Comments('note')), true))->toBe(['note']);
});

it('exposes headers as an array through debug info', function (): void {
    $headers = new Headers(['Language' => 'it']);

    expect(debugInfo($headers))->toBe(['Language' => 'it']);
});

it('exposes references as an array through debug info', function (): void {
    $references = new References();
    $references->add('file.php', 3);

    expect(debugInfo($references))->toBe(['file.php' => [3]]);
});

it('rebuilds references from state', function (): void {
    $references = References::__set_state([
        'references' => [
            'with-lines.php' => [1, 5],
            'bare.php' => [],
            'mixed.php' => [7, 'no-line', 9],
        ],
    ]);

    expect($references->toArray())->toBe([
        'with-lines.php' => [1, 5],
        'bare.php' => [],
        'mixed.php' => [7, 9],
    ]);
});

it('ignores malformed state', function (): void {
    $references = References::__set_state([
        'references' => [
            'valid.php' => [2],
            'broken.php' => 'not-an-array',
        ],
        'unknown-key' => true,
    ]);

    expect($references->toArray())->toBe(['valid.php' => [2]]);

    $empty = References::__set_state([]);

    expect($empty->toArray())->toBe([]);
});

/**
 * Invokes the magic __debugInfo() of an object without printing anything.
 *
 * @return array<string, mixed>
 */
function debugInfo(object $object): array
{
    $closure = Closure::bind(
        static fn (): array => $object->__debugInfo(), // @phpstan-ignore method.notFound, return.type
        null,
        $object::class
    );

    return $closure(); // @phpstan-ignore return.type
}
