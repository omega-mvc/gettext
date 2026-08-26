<?php

declare(strict_types=1);

namespace Omega\Gettext;

/**
 * Contract shared by every translator implementation.
 *
 * Each method mirrors a native gettext lookup: `d*` variants select the
 * domain, `p*` variants select the context, `n*` variants resolve plural
 * forms. Missing translations always return the original string, so calls
 * never fail because of an incomplete catalog.
 */
interface TranslatorInterface
{
    /**
     * Marks a string for extraction without translating it.
     *
     * @param string $original Source string to mark as translatable.
     *
     * @return string The same original string, unchanged.
     */
    public function noop(string $original): string;

    /**
     * Returns the translation of the original string.
     *
     * @param string $original Source string to translate.
     *
     * @return string The translated text, or the original when missing.
     */
    public function gettext(string $original): string;

    /**
     * Returns the translation for the singular or plural form of a string.
     *
     * @param string $original Singular source string.
     * @param string $plural Plural source string.
     * @param int $value Number used to pick the plural form.
     *
     * @return string The translated text, or the closest source form when missing.
     */
    public function ngettext(string $original, string $plural, int $value): string;

    /**
     * Same as ngettext() but restricted to the given domain.
     *
     * @param string $domain Domain to search in.
     * @param string $original Singular source string.
     * @param string $plural Plural source string.
     * @param int $value Number used to pick the plural form.
     *
     * @return string The translated text, or the closest source form when missing.
     */
    public function dngettext(string $domain, string $original, string $plural, int $value): string;

    /**
     * Same as ngettext() but restricted to the given context.
     *
     * @param string $context Context to search in.
     * @param string $original Singular source string.
     * @param string $plural Plural source string.
     * @param int $value Number used to pick the plural form.
     *
     * @return string The translated text, or the closest source form when missing.
     */
    public function npgettext(string $context, string $original, string $plural, int $value): string;

    /**
     * Returns the translation of the original string within a context.
     *
     * @param string $context Context to search in.
     * @param string $original Source string to translate.
     *
     * @return string The translated text, or the original when missing.
     */
    public function pgettext(string $context, string $original): string;

    /**
     * Returns the translation of the original string within a domain.
     *
     * @param string $domain Domain to search in.
     * @param string $original Source string to translate.
     *
     * @return string The translated text, or the original when missing.
     */
    public function dgettext(string $domain, string $original): string;

    /**
     * Returns the translation of the original string within a domain and context.
     *
     * @param string $domain Domain to search in.
     * @param string $context Context to search in.
     * @param string $original Source string to translate.
     *
     * @return string The translated text, or the original when missing.
     */
    public function dpgettext(string $domain, string $context, string $original): string;

    /**
     * Combines domain, context and plural resolution in a single lookup.
     *
     * @param string $domain Domain to search in.
     * @param string $context Context to search in.
     * @param string $original Singular source string.
     * @param string $plural Plural source string.
     * @param int $value Number used to pick the plural form.
     *
     * @return string The translated text, or the closest source form when missing.
     */
    public function dnpgettext(string $domain, string $context, string $original, string $plural, int $value): string;
}
