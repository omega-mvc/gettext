<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Omega\Gettext\Flags;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Tests\TestCase;

#[CoversClass(Flags::class)]
final class FlagsTest extends TestCase
{
    public function testManagesFlags(): void
    {
        $flags = new Flags();

        $this->assertSame([], $flags->toArray());
        $this->assertCount(0, $flags);

        $flags->add('foo');

        $this->assertSame(['foo'], $flags->toArray());
        $this->assertCount(1, $flags);

        $flags->add('foo');

        $this->assertSame(['foo'], $flags->toArray());
        $this->assertCount(1, $flags);

        $flags->add('bar');

        $this->assertSame(['bar', 'foo'], $flags->toArray());
        $this->assertCount(2, $flags);

        $flags->add('one', 'two', 'three');

        $this->assertSame(['bar', 'foo', 'one', 'three', 'two'], $flags->toArray());
        $this->assertCount(5, $flags);

        $flags->delete('bar', 'one', 'two');

        $this->assertSame(['foo', 'three'], $flags->toArray());
        $this->assertCount(2, $flags);
    }

    public function testMergesFlags(): void
    {
        $flags1 = new Flags('one', 'two', 'three');
        $flags2 = new Flags('three', 'four', 'five');

        $merged = $flags1->mergeWith($flags2);

        $this->assertCount(5, $merged);
        $this->assertSame([
            'five',
            'four',
            'one',
            'three',
            'two',
        ], $merged->toArray());

        $this->assertNotSame($flags1, $merged);
        $this->assertNotSame($flags2, $merged);
    }

    public function testCreatesAFlagsCollectionFromState(): void
    {
        $state = ['flags' => ['one', 'two']];
        $flags = Flags::__set_state($state);

        $this->assertCount(2, $flags);
        $this->assertSame($state['flags'], $flags->toArray());
    }
}
