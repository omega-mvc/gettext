<?php

declare(strict_types=1);

namespace Omega\Gettext;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

use function count;
use function in_array;
use function is_array;
use function is_int;

/**
 * Tracks where a translatable string occurs in the source code.
 *
 * References map each file name to the list of line numbers containing the
 * string. A file registered without line numbers is stored as an empty list
 * and rendered as a bare `#:` entry in the .po file.
 *
 * @phpstan-consistent-constructor
 *
 * @implements IteratorAggregate<string, list<int>>
 */
class References implements JsonSerializable, Countable, IteratorAggregate
{
    /** @var array<string, list<int>> Source file names mapped to their line numbers. */
    protected array $references = [];

    /**
     * Rebuilds an instance from a var_export() export.
     *
     * @param array<string, mixed> $state Exported state, holding a `references` array.
     *
     * @return static The reconstructed instance.
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

    /**
     * Registers a source occurrence of the translated string.
     *
     * Adding the same line twice is ignored. Passing null registers the bare
     * file name without any line information.
     *
     * @param string $filename Path of the source file, relative to the project root.
     * @param int|null $line Line number of the occurrence, if known.
     *
     * @return self This object, for method chaining.
     */
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
    /**
     * Serializes the references for json_encode().
     *
     * @return array<string, list<int>> File names mapped to their line numbers.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @return ArrayIterator<string, list<int>> */
    /**
     * Iterates over the references grouped by file name.
     *
     * @return ArrayIterator<string, list<int>> Iterator of file names and lines.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->references);
    }

    /**
     * Counts the total occurrences: one per line number, or one per file
     * when it has none. A file with several lines counts once per line,
     * so this is the number of `#:` entries the .po generator will emit.
     *
     * @return int Number of rendered reference entries.
     */
    public function count(): int
    {
        $total = 0;

        foreach ($this->references as $lines) {
            $total += count($lines) ?: 1;
        }

        return $total;
    }

    /**
     * Exports the references as a plain array.
     *
     * @return array<string, list<int>> File names mapped to their line numbers.
     */
    public function toArray(): array
    {
        return $this->references;
    }

    /**
     * Merges another set of references into this one without mutating it;
     * duplicated lines for the same file are skipped.
     *
     * @param References $references References to import.
     *
     * @return References A clone containing both sets of references.
     */
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
