<?php

declare(strict_types=1);

namespace Omega\Gettext\Scanner;

use Omega\Gettext\Translation;

/**
 * Trait with common gettext function handlers
 */
trait FunctionsHandlersTrait
{
    /**
     * Handles singular lookups without context or domain.
     *
     * @param ParsedFunction $function The parsed `__` / `gettext` call.
     * @return Translation|null The stored entry, or null when skipped.
     */
    protected function gettext(ParsedFunction $function): ?Translation
    {
        if (!$this->checkFunction($function, 1)) {
            return null;
        }
        [$original] = $function->getStringArguments(1);

        $translation = $this->addComments(
            $function,
            $this->saveTranslation(null, null, $original)
        );

        return $this->addFlags($function, $translation);
    }

    /**
     * Handles plural lookups without context or domain.
     *
     * @param ParsedFunction $function The parsed `n__` / `ngettext` call.
     * @return Translation|null The stored entry, or null when skipped.
     */
    protected function ngettext(ParsedFunction $function): ?Translation
    {
        if (!$this->checkFunction($function, 2)) {
            return null;
        }
        [$original, $plural] = $function->getStringArguments(2);

        $translation = $this->addComments(
            $function,
            $this->saveTranslation(null, null, $original, $plural)
        );

        return $this->addFlags($function, $translation);
    }

    /**
     * Handles singular lookups with context.
     *
     * @param ParsedFunction $function The parsed `p__` / `pgettext` call.
     * @return Translation|null The stored entry, or null when skipped.
     */
    protected function pgettext(ParsedFunction $function): ?Translation
    {
        if (!$this->checkFunction($function, 2)) {
            return null;
        }
        [$context, $original] = $function->getStringArguments(2);

        $translation = $this->addComments(
            $function,
            $this->saveTranslation(null, $context, $original)
        );

        return $this->addFlags($function, $translation);
    }

    /**
     * Handles singular lookups with domain.
     *
     * @param ParsedFunction $function The parsed `d__` / `dgettext` call.
     * @return Translation|null The stored entry, or null when skipped.
     */
    protected function dgettext(ParsedFunction $function): ?Translation
    {
        if (!$this->checkFunction($function, 2)) {
            return null;
        }
        [$domain, $original] = $function->getStringArguments(2);

        $translation = $this->addComments(
            $function,
            $this->saveTranslation($domain, null, $original)
        );

        return $this->addFlags($function, $translation);
    }

    /**
     * Handles singular lookups with domain and context.
     *
     * @param ParsedFunction $function The parsed `dp__` / `dpgettext` call.
     * @return Translation|null The stored entry, or null when skipped.
     */
    protected function dpgettext(ParsedFunction $function): ?Translation
    {
        if (!$this->checkFunction($function, 3)) {
            return null;
        }
        [$domain, $context, $original] = $function->getStringArguments(3);

        $translation = $this->addComments(
            $function,
            $this->saveTranslation($domain, $context, $original)
        );

        return $this->addFlags($function, $translation);
    }

    /**
     * Handles plural lookups with context.
     *
     * @param ParsedFunction $function The parsed `np__` / `npgettext` call.
     * @return Translation|null The stored entry, or null when skipped.
     */
    protected function npgettext(ParsedFunction $function): ?Translation
    {
        if (!$this->checkFunction($function, 3)) {
            return null;
        }
        [$context, $original, $plural] = $function->getStringArguments(3);

        $translation = $this->addComments(
            $function,
            $this->saveTranslation(null, $context, $original, $plural)
        );

        return $this->addFlags($function, $translation);
    }

    /**
     * Handles plural lookups with domain.
     *
     * @param ParsedFunction $function The parsed `dn__` / `dngettext` call.
     * @return Translation|null The stored entry, or null when skipped.
     */
    protected function dngettext(ParsedFunction $function): ?Translation
    {
        if (!$this->checkFunction($function, 3)) {
            return null;
        }
        [$domain, $original, $plural] = $function->getStringArguments(3);

        $translation = $this->addComments(
            $function,
            $this->saveTranslation($domain, null, $original, $plural)
        );

        return $this->addFlags($function, $translation);
    }

    /**
     * Handles plural lookups with domain and context.
     *
     * @param ParsedFunction $function The parsed `dnp__` / `dnpgettext` call.
     * @return Translation|null The stored entry, or null when skipped.
     */
    protected function dnpgettext(ParsedFunction $function): ?Translation
    {
        if (!$this->checkFunction($function, 4)) {
            return null;
        }
        [$domain, $context, $original, $plural] = $function->getStringArguments(4);

        $translation = $this->addComments(
            $function,
            $this->saveTranslation($domain, $context, $original, $plural)
        );

        return $this->addFlags($function, $translation);
    }

    abstract protected function addComments(ParsedFunction $function, ?Translation $translation): ?Translation;

    abstract protected function addFlags(ParsedFunction $function, ?Translation $translation): ?Translation;

    abstract protected function checkFunction(ParsedFunction $function, int $minLength): bool;

    abstract protected function saveTranslation(
        ?string $domain,
        ?string $context,
        string $original,
        ?string $plural = null
    ): ?Translation;
}
