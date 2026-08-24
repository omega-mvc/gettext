<?php

declare(strict_types=1);

namespace Gettext;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * Class to manage the flags of a translation.
 *
 * @phpstan-consistent-constructor
 *
 * @implements IteratorAggregate<int, string>
 */
class Flags implements JsonSerializable, Countable, IteratorAggregate
{
    /** @var list<string> */
    protected array $flags = [];

    /**
     * @param array<string, mixed> $state
     */
    public static function __set_state(array $state): Flags
    {
        $stateFlags = $state['flags'] ?? [];

        return new static(
            ...(is_array($stateFlags) ? array_values(array_map('strval', $stateFlags)) : [])
        );
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

    public function has(string $flag): bool
    {
        return in_array($flag, $this->flags, true);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->flags);
    }

    public function count(): int
    {
        return count($this->flags);
    }

    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        return $this->flags;
    }

    public function mergeWith(Flags $flags): Flags
    {
        $merged = clone $this;
        $merged->add(...$flags->flags);

        return $merged;
    }
}
