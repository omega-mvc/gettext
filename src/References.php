<?php

declare(strict_types=1);

namespace Gettext;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * Class to manage the references of a translation.
 *
 * @phpstan-consistent-constructor
 *
 * @implements IteratorAggregate<string, list<int>>
 */
class References implements JsonSerializable, Countable, IteratorAggregate
{
    /** @var array<string, list<int>> */
    protected array $references = [];

    /**
     * @param array<string, mixed> $state
     */
    public static function __set_state(array $state): References
    {
        $references = new static();
        $stateReferences = $state['references'] ?? [];

        if (is_array($stateReferences)) {
            foreach ($stateReferences as $filename => $lines) {
                if (!is_array($lines)) {
                    continue;
                }

                $name = (string) $filename;

                if ($lines === []) {
                    $references->add($name);
                    continue;
                }

                foreach ($lines as $line) {
                    $references->add($name, is_int($line) ? $line : null);
                }
            }
        }

        return $references;
    }

    public function __construct()
    {
    }

    public function __debugInfo()
    {
        return $this->toArray();
    }

    public function add(string $filename, ?int $line = null): self
    {
        $fileReferences = $this->references[$filename] ?? [];

        if (isset($line) && !in_array($line, $fileReferences)) {
            $fileReferences[] = $line;
        }

        $this->references[$filename] = $fileReferences;

        return $this;
    }

    /** @return array<string, list<int>> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @return ArrayIterator<string, list<int>> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->references);
    }

    public function count(): int
    {
        $total = 0;

        foreach ($this->references as $lines) {
            $total += count($lines) ?: 1;
        }

        return $total;
    }

    /**
     * @return array<string, list<int>>
     */
    public function toArray(): array
    {
        return $this->references;
    }

    public function mergeWith(References $references): References
    {
        $merged = clone $this;

        foreach ($references as $filename => $lines) {
            //Set filename always to string
            $filename = (string) $filename;

            if (empty($lines)) {
                $merged->add($filename);
                continue;
            }

            foreach ($lines as $line) {
                $merged->add($filename, $line);
            }
        }

        return $merged;
    }
}
