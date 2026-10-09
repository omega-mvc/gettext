<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Omega\Gettext\Comments;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Tests\TestCase;

#[CoversClass(Comments::class)]
final class CommentsTest extends TestCase
{
    public function testManagesComments(): void
    {
        $comments = new Comments();

        $this->assertSame([], $comments->toArray());
        $this->assertCount(0, $comments);

        $comments->add('foo');

        $this->assertSame(['foo'], $comments->toArray());
        $this->assertCount(1, $comments);

        $comments->add('foo');

        $this->assertSame(['foo'], $comments->toArray());
        $this->assertCount(1, $comments);

        $comments->add('bar');

        $this->assertSame(['foo', 'bar'], $comments->toArray());
        $this->assertCount(2, $comments);

        $comments->delete('foo');

        $this->assertSame(['bar'], $comments->toArray());
        $this->assertCount(1, $comments);
    }

    public function testMergesComments(): void
    {
        $comments1 = new Comments('one', 'two', 'three');
        $comments2 = new Comments('three', 'four', 'five');

        $merged = $comments1->mergeWith($comments2);

        $this->assertCount(5, $merged);
        $this->assertSame(['one', 'two', 'three', 'four', 'five'], $merged->toArray());

        $this->assertNotSame($comments1, $merged);
        $this->assertNotSame($comments2, $merged);
    }

    public function testCreatesACommentsCollectionFromState(): void
    {
        $state = ['comments' => ['First comment', 'Second comment']];
        $comments = Comments::__set_state($state);

        $this->assertCount(2, $comments);
        $this->assertSame($state['comments'], $comments->toArray());
    }
}
