<?php

declare(strict_types=1);

namespace Omega\Gettext\Scanner;

use Exception;
use Omega\Gettext\Translation;

use function array_slice;
use function array_values;
use function call_user_func;
use function is_callable;
use function is_null;
use function is_string;
use function sprintf;
use function strpos;

/**
 * Base class with common functions to scan files with code and get gettext translations.
 */
abstract class CodeScanner extends Scanner
{
    protected bool $ignoreInvalidFunctions = false;

    protected bool $addReferences = true;

    /** @var list<string> */
    protected array $commentsPrefixes = [];

    /** @var array<string, string> */
    protected array $functions = [];

    /**
     * @param array<string, string> $functions [fnName => handler]
     */
    public function setFunctions(array $functions): self
    {
        $this->functions = $functions;

        return $this;
    }

    /**
     * @return array<string, string> [fnName => handler]
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * Configures the behavior when a gettext call has missing or non-string required arguments.
     *
     * @param bool $ignore True to skip invalid calls silently; false (default) throws.
     * @return self This object, for method chaining.
     */
    public function ignoreInvalidFunctions(bool $ignore = true): self
    {
        $this->ignoreInvalidFunctions = $ignore;

        return $this;
    }

    /**
     * Enables or disables recording of source references.
     *
     * @param bool $enabled True to record file and line of every found entry.
     * @return self This object, for method chaining.
     */
    public function addReferences(bool $enabled = true): self
    {
        $this->addReferences = $enabled;

        return $this;
    }

    /**
     * Sets which developer comments are extracted as extracted comments.
     *
     * @param string ...$prefixes Comment prefixes to capture (e.g. `i18n:`, `Translators:`).
     * @return self This object, for method chaining.
     */
    public function extractCommentsStartingWith(string ...$prefixes): self
    {
        $this->commentsPrefixes = array_values($prefixes);

        return $this;
    }

    /**
     * Extracts every known gettext call from the given content.
     *
     * @param string $string Source code content to scan.
     * @param string $filename Name reported in the references of found entries.
     * @throws \Exception If a scanned call is invalid and ignoreInvalidFunctions is off.
     */
    public function scanString(string $string, string $filename): void
    {
        $functionsScanner = $this->getFunctionsScanner();
        $functions = $functionsScanner->scan($string, $filename);

        foreach ($functions as $function) {
            $this->handleFunction($function);
        }
    }

    abstract public function getFunctionsScanner(): FunctionsScannerInterface;

    /**
     * Resolves the handler for a parsed call and stores its translation.
     *
     * @param ParsedFunction $function The parsed call to process.
     */
    protected function handleFunction(ParsedFunction $function): void
    {
        $handler = $this->getFunctionHandler($function);

        if (is_null($handler)) {
            return;
        }

        $translation = call_user_func($handler, $function);

        if ($translation instanceof Translation && $this->addReferences) {
            $translation->getReferences()->add($function->getFilename(), $function->getLine());
        }
    }

    /**
     * Maps a function name to its handler using the configured functions map.
     *
     * @param ParsedFunction $function The parsed call whose name is looked up.
     * @return callable|null A [self, method] pair, or null when the name is unknown.
     */
    protected function getFunctionHandler(ParsedFunction $function): ?callable
    {
        $name = $function->getName();
        $handler = $this->functions[$name] ?? null;

        if ($handler === null) {
            return null;
        }

        $callback = [$this, $handler];

        return is_callable($callback) ? $callback : null;
    }

    /**
     * Copies matching developer comments into the extracted comments.
     *
     * @param ParsedFunction $function Call carrying the comments.
     * @param Translation|null $translation Entry receiving them, or null to skip.
     * @return Translation|null The same translation passed in.
     */
    protected function addComments(ParsedFunction $function, ?Translation $translation): ?Translation
    {
        if (empty($this->commentsPrefixes) || $translation === null) {
            return $translation;
        }

        foreach ($function->getComments() as $comment) {
            if ($this->checkComment($comment)) {
                $translation->getExtractedComments()->add($comment);
            }
        }

        return $translation;
    }

    /**
     * Copies the flags found near the call into the translation.
     *
     * @param ParsedFunction $function Call carrying the flags.
     * @param Translation|null $translation Entry receiving them, or null to skip.
     * @return Translation|null The same translation passed in.
     */
    protected function addFlags(ParsedFunction $function, ?Translation $translation): ?Translation
    {
        if ($translation === null) {
            return $translation;
        }

        foreach ($function->getFlags() as $flag) {
            $translation->getFlags()->add($flag);
        }

        return $translation;
    }

    /**
     * Validates that the required leading arguments exist and are strings.
     *
     * @param ParsedFunction $function Call being validated.
     * @param int $minLength Number of leading string arguments required by the handler.
     * @return bool True when valid; false when invalid and ignoreInvalidFunctions is on.
     * @throws \Exception If invalid and ignoreInvalidFunctions is off.
     */
    protected function checkFunction(ParsedFunction $function, int $minLength): bool
    {
        if ($function->countArguments() < $minLength) {
            if ($this->ignoreInvalidFunctions) {
                return false;
            }

            throw new Exception(
                sprintf(
                    'Invalid gettext function in %s:%d. At least %d arguments are required',
                    $function->getFilename(),
                    $function->getLine(),
                    $minLength
                )
            );
        }

        $arguments = array_slice($function->getArguments(), 0, $minLength);

        foreach ($arguments as $argument) {
            if (!is_string($argument)) {
                if ($this->ignoreInvalidFunctions) {
                    return false;
                }

                throw new Exception(
                    sprintf(
                        'Invalid gettext function in %s:%d. Some required arguments are not valid',
                        $function->getFilename(),
                        $function->getLine()
                    )
                );
            }
        }

        return true;
    }

    /**
     * Checks whether a comment matches one of the configured prefixes.
     *
     * @param string $comment Comment text to test against the configured prefixes.
     * @return bool True when at least one prefix matches.
     */
    protected function checkComment(string $comment): bool
    {
        foreach ($this->commentsPrefixes as $prefix) {
            if ($prefix === '' || strpos($comment, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }
}
