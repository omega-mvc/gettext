<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Omega\Gettext\Comments;

covers(Comments::class);

it('manages comments', function (): void {
    $comments = new Comments();

    expect($comments->toArray())->toBe([]);
    expect($comments)->toHaveCount(0);

    $comments->add('foo');

    expect($comments->toArray())->toBe(['foo']);
    expect($comments)->toHaveCount(1);

    $comments->add('foo');

    expect($comments->toArray())->toBe(['foo']);
    expect($comments)->toHaveCount(1);

    $comments->add('bar');

    expect($comments->toArray())->toBe(['foo', 'bar']);
    expect($comments)->toHaveCount(2);

    $comments->delete('foo');

    expect($comments->toArray())->toBe(['bar']);
    expect($comments)->toHaveCount(1);
});

it('merges comments', function (): void {
    $comments1 = new Comments('one', 'two', 'three');
    $comments2 = new Comments('three', 'four', 'five');

    $merged = $comments1->mergeWith($comments2);

    expect($merged)->toHaveCount(5);
    expect($merged->toArray())->toBe(['one', 'two', 'three', 'four', 'five']);

    expect($merged)->not->toBe($comments1);
    expect($merged)->not->toBe($comments2);
});

it('creates a comments collection from state', function (): void {
    $state = ['comments' => ['First comment', 'Second comment']];
    $comments = Comments::__set_state($state);

    expect($comments)->toHaveCount(2);
    expect($comments->toArray())->toBe($state['comments']);
});
