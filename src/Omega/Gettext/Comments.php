<?php

declare(strict_types=1);

namespace Omega\Gettext;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

use function array_search;
use function array_splice;
use function count;
use function in_array;
use function is_array;
use function is_int;
use function is_string;

/**
 * Stores the comment entries attached to a translation.
 *
 * A translation owns two independent instances of this class: translator
 * comments and extracted comments. Entries are unique and keep insertion
 * order, so they are rendered exactly as authored in the .po file.
 *
 * @phpstan-consistent-constructor
 *
 * @implements IteratorAggregate<int, string>
 */
class Comments implements JsonSerializable, Countable, IteratorAggregate
{
    /** @var list<string> Unique comments in insertion order. */
    protected array $comments = [];

    /**
     * Rebuilds an instance from a var_export() export.
     *
     * @param array<string, mixed> $state Exported state, holding a `comments` array.
     *
     * @return static The reconstructed instance.
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

    /**
     * Appends one or more comments, ignoring duplicates.
     *
     * @param string ...$comments Comment texts to append.
     *
     * @return self This object, for method chaining.
     */
    public function add(string ...$comments): self
    {
        foreach ($comments as $comment) {
            if (!in_array($comment, $this->comments)) {
                $this->comments[] = $comment;
            }
        }

        return $this;
    }

    /**
     * Removes one or more previously added comments; unknown ones are ignored.
     *
     * @param string ...$comments Comment texts to remove.
     *
     * @return self This object, for method chaining.
     */
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

    /**
     * Serializes the comments for json_encode().
     *
     * @return list<string> The comment texts in insertion order.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @return ArrayIterator<int, string> */
    /**
     * Iterates over the comment texts.
     *
     * @return ArrayIterator<int, string> Iterator of comment texts.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->comments);
    }

    /**
     * Counts the stored comments.
     *
     * @return int Number of unique comments.
     */
    public function count(): int
    {
        return count($this->comments);
    }

    /**
     * Exports the comments as a plain list.
     *
     * @return list<string> The comment texts in insertion order.
     */
    public function toArray(): array
    {
        return $this->comments;
    }

    /**
     * Merges another set of comments into this one without mutating it.
     *
     * @param Comments $comments Comments to import; duplicates are skipped.
     *
     * @return Comments A clone containing both sets of comments.
     */
    public function mergeWith(Comments $comments): Comments
    {
        $merged = clone $this;
        $merged->add(...$comments->comments);

        return $merged;
    }
}
