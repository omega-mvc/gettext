<?php

declare(strict_types=1);

namespace Omega\Gettext\Scanner;

use function array_slice;
use function count;
use function is_string;

/**
 * Class to handle the info of a parsed function.
 */
final class ParsedFunction
{
    private string $name;
    private string $filename;
    private int $line;
    private int $lastLine;

    /** @var list<mixed> */
    private array $arguments = [];

    /** @var list<string> */
    private array $comments = [];

    /** @var list<string> */
    private array $flags = [];

    /**
     * @param string $name Called function name, e.g. `__` or `n__`.
     * @param string $filename File where the call was found.
     * @param int $line Line of the first token of the call.
     * @param int|null $lastLine Last line of the call; defaults to $line.
     */
    public function __construct(string $name, string $filename, int $line, ?int $lastLine = null)
    {
        $this->name = $name;
        $this->filename = $filename;
        $this->line = $line;
        $this->lastLine = $lastLine ?? $line;
    }

    /**
     * @return array<string, mixed> Compact representation for var_dump().
     */
    public function __debugInfo(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'filename' => $this->filename,
            'line' => $this->line,
            'lastLine' => $this->lastLine,
            'arguments' => $this->arguments,
            'comments' => $this->comments,
            'flags' => $this->flags,
        ];
    }

    /**
     * @return string The called function name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int First line of the call.
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @return int Last line of the call.
     */
    public function getLastLine(): int
    {
        return $this->lastLine;
    }

    /**
     * @return string File where the call was found.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return list<mixed>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Returns the leading arguments as strings.
     *
     * Call it after checkFunction() has validated the required arguments,
     * so exactly $count entries are expected; non-string entries are skipped.
     *
     * @return list<string>
     */
    public function getStringArguments(int $count): array
    {
        $strings = [];

        foreach (array_slice($this->arguments, 0, $count) as $value) {
            if (is_string($value)) {
                $strings[] = $value;
            }
        }

        return $strings;
    }

    /**
     * @return int Number of captured arguments.
     */
    public function countArguments(): int
    {
        return count($this->arguments);
    }

    /**
     * @return list<string>
     */
    public function getComments(): array
    {
        return $this->comments;
    }

    /**
     * @return list<string>
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    /**
     * Appends one captured argument to the list.
     *
     * @param mixed $argument Extracted literal value, or null when it is dynamic.
     */
    public function addArgument(mixed $argument = null): self
    {
        $this->arguments[] = $argument;

        return $this;
    }

    /**
     * Appends a comment associated with this call.
     *
     * @param string $comment Related comment text.
     */
    public function addComment(string $comment): self
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Appends a flag associated with this call.
     *
     * @param string $flag Flag text (e.g. php-format).
     */
    public function addFlag(string $flag): self
    {
        $this->flags[] = $flag;

        return $this;
    }
}
