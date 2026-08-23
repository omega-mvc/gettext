<?php

declare(strict_types=1);

namespace Gettext\Scanner;

/**
 * Class to handle the info of a parsed function.
 */
final class ParsedFunction
{
    private $name;
    private $filename;
    private $line;
    private $lastLine;
    private $arguments = [];
    private $comments = [];
    private $flags = [];

    public function __construct(string $name, string $filename, int $line, ?int $lastLine = null)
    {
        $this->name = $name;
        $this->filename = $filename;
        $this->line = $line;
        $this->lastLine = $lastLine ?? $line;
    }

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

    public function getName(): string
    {
        return $this->name;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getLastLine(): int
    {
        return $this->lastLine;
    }

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

    public function addArgument($argument = null): self
    {
        $this->arguments[] = $argument;

        return $this;
    }

    public function addComment(string $comment): self
    {
        $this->comments[] = $comment;

        return $this;
    }

    public function addFlag(string $flag): self
    {
        $this->flags[] = $flag;

        return $this;
    }
}
