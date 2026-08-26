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
use function sort;

/**
 * Stores the gettext flags attached to a translation, such as `fuzzy`
 * or `php-format`.
 *
 * Flags are unique and kept alphabetically sorted, so they render as a
 * single comma-separated `#,` line in the .po file.
 *
 * @phpstan-consistent-constructor
 *
 * @implements IteratorAggregate<int, string>
 */
class Flags implements JsonSerializable, Countable, IteratorAggregate
{
    /** @var list<string> Unique flags kept in alphabetical order. */
    protected array $flags = [];

    /**
     * Rebuilds an instance from a var_export() export.
     *
     * @param array<string, mixed> $state Exported state, holding a `flags` array.
     *
     * @return static The reconstructed instance.
     */
    public static function __set_state(array $state): Flags
    {
        $stateFlags = $state['flags'] ?? [];

        $flags = [];

        if (is_array($stateFlags)) {
            foreach ($stateFlags as $item) {
                if (is_string($item)) {
                    $flags[] = $item;
                }
            }
        }

        return new static(...$flags);
    }

    public function __construct(string ...$flags)
    {
        if (!empty($flags)) {
            $this->add(...$flags);
        }
    }

    public function __debugInfo()
    {
        return $this->toArray();
    }

    /**
     * Adds one or more flags, ignoring duplicates, then re-sorts them.
     *
     * @param string ...$flags Flag names to add (e.g. `fuzzy`, `php-format`).
     *
     * @return self This object, for method chaining.
     */
    public function add(string ...$flags): self
    {
        foreach ($flags as $flag) {
            if (!$this->has($flag)) {
                $this->flags[] = $flag;
            }
        }

        sort($this->flags);

        return $this;
    }

    /**
     * Removes one or more flags; unknown ones are ignored.
     *
     * @param string ...$flags Flag names to remove.
     *
     * @return self This object, for method chaining.
     */
    public function delete(string ...$flags): self
    {
        foreach ($flags as $flag) {
            $key = array_search($flag, $this->flags);

            if (is_int($key)) {
                array_splice($this->flags, $key, 1);
            }
        }

        return $this;
    }

    /**
     * Checks whether a flag is present.
     *
     * @param string $flag Flag name to look up.
     *
     * @return bool True if the flag is set.
     */
    public function has(string $flag): bool
    {
        return in_array($flag, $this->flags, true);
    }

    /**
     * Serializes the flags for json_encode().
     *
     * @return list<string> The flag names in alphabetical order.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @return ArrayIterator<int, string> */
    /**
     * Iterates over the flag names.
     *
     * @return ArrayIterator<int, string> Iterator of flag names.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->flags);
    }

    /**
     * Counts the stored flags.
     *
     * @return int Number of unique flags.
     */
    public function count(): int
    {
        return count($this->flags);
    }

    /**
     * Exports the flags as a plain list.
     *
     * @return list<string> The flag names in alphabetical order.
     */
    public function toArray(): array
    {
        return $this->flags;
    }

    /**
     * Merges another set of flags into this one without mutating it.
     *
     * @param Flags $flags Flags to import; duplicates are skipped.
     *
     * @return Flags A clone containing both sets of flags.
     */
    public function mergeWith(Flags $flags): Flags
    {
        $merged = clone $this;
        $merged->add(...$flags->flags);

        return $merged;
    }
}
