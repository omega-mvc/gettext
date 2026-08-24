<?php

declare(strict_types=1);

namespace Gettext;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * Class to manage the comments of a translation.
 *
 * @phpstan-consistent-constructor
 *
 * @implements IteratorAggregate<int, string>
 */
class Comments implements JsonSerializable, Countable, IteratorAggregate
{
    /** @var list<string> */
    protected array $comments = [];

    /**
     * @param array<string, mixed> $state
     */
    public static function __set_state(array $state): Comments
    {
        $stateComments = $state['comments'] ?? [];

        $comments = [];

        if (is_array($stateComments)) {
            foreach ($stateComments as $item) {
                if (is_string($item)) {
                    $comments[] = $item;
                }
            }
        }

        return new static(...$comments);
    }

    public function __construct(string ...$comments)
    {
        if (!empty($comments)) {
            $this->add(...$comments);
        }
    }

    public function __debugInfo()
    {
        return $this->toArray();
    }

    public function add(string ...$comments): self
    {
        foreach ($comments as $comment) {
            if (!in_array($comment, $this->comments)) {
                $this->comments[] = $comment;
            }
        }

        return $this;
    }

    public function delete(string ...$comments): self
    {
        foreach ($comments as $comment) {
            $key = array_search($comment, $this->comments);

            if (is_int($key)) {
                array_splice($this->comments, $key, 1);
            }
        }

        return $this;
    }

    /** @return list<string> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @return ArrayIterator<int, string> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->comments);
    }

    public function count(): int
    {
        return count($this->comments);
    }

    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        return $this->comments;
    }

    public function mergeWith(Comments $comments): Comments
    {
        $merged = clone $this;
        $merged->add(...$comments->comments);

        return $merged;
    }
}
