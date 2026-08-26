<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Closure;
use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\References;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(References::class)]
class MetadataTest extends TestCase
{
    public function testFlagsAreIterableAndJsonSerializable(): void
    {
        $flags = new Flags('b', 'a');

        $values = [];

        foreach ($flags as $flag) {
            $values[] = $flag;
        }

        $this->assertSame(['a', 'b'], $values);
        $this->assertSame(['a', 'b'], json_decode((string) json_encode($flags), true));
    }

    public function testCollectionDebugInfoExposesArrays(): void
    {
        $this->assertSame(['x'], $this->debugInfo(new Flags('x')));
        $this->assertSame(['note'], $this->debugInfo(new Comments('note')));
        $this->assertSame(['note'], json_decode((string) json_encode(new Comments('note')), true));
    }

    public function testHeadersDebugInfoExposesArray(): void
    {
        $headers = new Headers(['Language' => 'it']);

        $this->assertSame(['Language' => 'it'], $this->debugInfo($headers));
    }

    public function testReferencesDebugInfoExposesArray(): void
    {
        $references = new References();
        $references->add('file.php', 3);

        $this->assertSame(['file.php' => [3]], $this->debugInfo($references));
    }

    public function testSetStateRebuildsReferences(): void
    {
        $references = References::__set_state([
            'references' => [
                'with-lines.php' => [1, 5],
                'bare.php' => [],
                'mixed.php' => [7, 'no-line', 9],
            ],
        ]);

        $this->assertSame([
            'with-lines.php' => [1, 5],
            'bare.php' => [],
            'mixed.php' => [7, 9],
        ], $references->toArray());
    }

    public function testSetStateIgnoresMalformedState(): void
    {
        $references = References::__set_state([
            'references' => [
                'valid.php' => [2],
                'broken.php' => 'not-an-array',
            ],
            'unknown-key' => true,
        ]);

        $this->assertSame(['valid.php' => [2]], $references->toArray());

        $empty = References::__set_state([]);

        $this->assertSame([], $empty->toArray());
    }

    /**
     * Invokes the magic __debugInfo() of an object without printing anything.
     *
     * @return array<string, mixed>
     */
    private function debugInfo(object $object): array
    {
        $closure = Closure::bind(
            static fn (): array => $object->__debugInfo(), // @phpstan-ignore method.notFound, return.type
            null,
            $object::class
        );

        return $closure(); // @phpstan-ignore return.type
    }
}
