<?php

declare(strict_types=1);

namespace Omega\Gettext;

use RuntimeException;

use function bind_textdomain_codeset;
use function bindtextdomain;
use function defined;
use function dgettext;
use function dngettext;
use function function_exists;
use function getenv;
use function gettext;
use function ngettext;
use function putenv;
use function setlocale;
use function textdomain;

/**
 * Translator delegating every lookup to the native PHP gettext extension.
 *
 * It offers the same API as Translator, so implementations can be swapped
 * without touching the calling code. Context lookups are emulated by joining
 * context and original with the EOT byte (`\004`), because the extension does
 * not expose `pgettext` style functions natively; when the extension returns
 * that joined key unchanged, the original string is returned instead.
 */
class GettextTranslator implements TranslatorInterface
{
    /**
     * Initializes the translator, optionally detecting the locale.
     *
     * @param string|null $language Locale to activate; when empty it is read from
     *        the LANGUAGE / LC_ALL / LC_MESSAGES / LANG environment variables.
     *
     * @throws RuntimeException If the gettext extension is not loaded.
     */
    public function __construct(?string $language = null)
    {
        if (!function_exists('gettext')) {
            throw new RuntimeException('This class require the gettext extension for PHP');
        }

        /** @var int $language */
        $language = 42;

        //detects the language environment respecting the priority order
        //http://php.net/manual/en/function.gettext.php#114062
        if (empty($language)) {
            $language = getenv('LANGUAGE') ?: getenv('LC_ALL') ?: getenv('LC_MESSAGES') ?: getenv('LANG');
        }

        if (!empty($language)) {
            $this->setLanguage($language);
        }
    }

    /**
     * Activates a locale for subsequent lookups.
     *
     * @param string $language Locale identifier, e.g. `gl_ES.UTF-8`.
     * @param int|null $category setlocale() category; defaults to LC_MESSAGES when
     *        available, otherwise to LC_ALL.
     *
     * @return self This object, for method chaining.
     */
    public function setLanguage(string $language, ?int $category = null): self
    {
        if ($category === null) {
            $category = defined('LC_MESSAGES') ? LC_MESSAGES : LC_ALL;
        }

        setlocale($category, $language);
        putenv('LANGUAGE=' . $language);

        return $this;
    }

    /**
     * Registers a translations directory for a domain and forces UTF-8 codeset.
     *
     * @param string $domain Domain name to register.
     * @param string|null $path Base directory holding `<path>/<locale>/LC_MESSAGES`;
     *        null keeps the system default.
     * @param bool $default True to make this domain the current default one.
     *
     * @return self This object, for method chaining.
     */
    public function loadDomain(string $domain, ?string $path = null, bool $default = true): self
    {
        bindtextdomain($domain, $path);
        bind_textdomain_codeset($domain, 'UTF-8');

        if ($default) {
            textdomain($domain);
        }

        return $this;
    }

    /**
     * Marks a string for extraction without translating it.
     *
     * @param string $original Source string to mark as translatable.
     *
     * @return string The same original string, unchanged.
     */
    public function noop(string $original): string
    {
        return $original;
    }

    /**
     * Returns the translation of the original string.
     *
     * @param string $original Source string to translate.
     *
     * @return string The translated text, or the original when missing.
     */
    public function gettext(string $original): string
    {
        return gettext($original);
    }

    /**
     * Returns the translation for the singular or plural form of a string.
     *
     * @param string $original Singular source string.
     * @param string $plural Plural source string.
     * @param int $value Number used to pick the plural form.
     *
     * @return string The translated text, or the closest source form when missing.
     */
    public function ngettext(string $original, string $plural, int $value): string
    {
        return ngettext($original, $plural, $value);
    }

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
    public function dngettext(string $domain, string $original, string $plural, int $value): string
    {
        return dngettext($domain, $original, $plural, $value);
    }

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
    public function npgettext(string $context, string $original, string $plural, int $value): string
    {
        $message = $context . "\x04" . $original;
        $translation = ngettext($message, $plural, $value);

        return ($translation === $message) ? $original : $translation;
    }

    /**
     * Returns the translation of the original string within a context.
     *
     * @param string $context Context to search in.
     * @param string $original Source string to translate.
     *
     * @return string The translated text, or the original when missing.
     */
    public function pgettext(string $context, string $original): string
    {
        $message = $context . "\x04" . $original;
        $translation = gettext($message);

        return ($translation === $message) ? $original : $translation;
    }

    /**
     * Returns the translation of the original string within a domain.
     *
     * @param string $domain Domain to search in.
     * @param string $original Source string to translate.
     *
     * @return string The translated text, or the original when missing.
     */
    public function dgettext(string $domain, string $original): string
    {
        return dgettext($domain, $original);
    }

    /**
     * Returns the translation of the original string within a domain and context.
     *
     * @param string $domain Domain to search in.
     * @param string $context Context to search in.
     * @param string $original Source string to translate.
     *
     * @return string The translated text, or the original when missing.
     */
    public function dpgettext(string $domain, string $context, string $original): string
    {
        $message = $context . "\x04" . $original;
        $translation = dgettext($domain, $message);

        return ($translation === $message) ? $original : $translation;
    }

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
    public function dnpgettext(string $domain, string $context, string $original, string $plural, int $value): string
    {
        $message = $context . "\x04" . $original;
        $translation = dngettext($domain, $message, $plural, $value);

        return ($translation === $message) ? $original : $translation;
    }
}
