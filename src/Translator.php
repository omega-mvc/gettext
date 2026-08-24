<?php

declare(strict_types=1);

namespace Gettext;

use Closure;
use Gettext\Generator\ArrayGenerator;
use InvalidArgumentException;

use function get_debug_type;
use function sprintf;

/**
 * @phpstan-consistent-constructor
 */
class Translator implements TranslatorInterface
{
    protected ?string $domain = null;

    /** @var array<string, array<string, array<string, list<string>|string>>> */
    protected array $dictionary = [];

    /** @var array<string, array{count: int, code: string, function?: Closure}> */
    protected array $plurals = [];

    public static function createFromTranslations(Translations ...$allTranslations): Translator
    {
        $translator = new static();
        $arrayGenerator = new ArrayGenerator();

        foreach ($allTranslations as $translations) {
            $translator->addTranslations(
                $arrayGenerator->generateArray($translations)
            );
        }

        return $translator;
    }

    /**
     * Load new translations from php files
     */
    public function loadTranslations(string ...$files): self
    {
        foreach ($files as $file) {
            $translations = include $file;

            if (!is_array($translations)) {
                throw new InvalidArgumentException('Invalid translations file: it must return an array');
            }

            $this->addTranslations($translations);
        }

        return $this;
    }

    /**
     * Add new translations to the dictionary.
     *
     * @param array<array-key, mixed> $translations
     */
    public function addTranslations(array $translations): self
    {
        $domain = $translations['domain'] ?? '';

        if (!is_string($domain)) {
            $domain = '';
        }

        //Set the first domain loaded as default domain
        if ($this->domain === null) {
            $this->domain = $domain;
        }

        $messages = $translations['messages'] ?? [];

        if (!is_array($messages)) {
            $messages = [];
        }

        if (isset($this->dictionary[$domain])) {
            $merged = array_replace_recursive($this->dictionary[$domain], self::normalizeMessages($messages));

            $this->dictionary[$domain] = self::normalizeMessages($merged);

            return $this;
        }

        $pluralForms = $translations['plural-forms'] ?? null;

        if (is_string($pluralForms) && $pluralForms !== '') {
            $chunks = explode(';', $pluralForms, 2);
            $count = trim($chunks[0]);
            $code = trim($chunks[1] ?? '');

            // extract just the expression turn 'n' into a php variable '$n'.
            // Slap on a return keyword and semicolon at the end.
            $this->plurals[$domain] = [
                'count' => (int) str_replace('nplurals=', '', $count),
                'code' => 'return ' . self::fixTerseIfs(rtrim(str_replace('plural=', '', $code), ';')) . ';',
            ];
        }

        $this->dictionary[$domain] = self::normalizeMessages($messages);

        return $this;
    }

    /**
     * Keeps only the well-formed entries of a messages structure:
     * context => original => (string translation | list<string> plural translations).
     *
     * @param array<array-key, mixed> $messages
     * @return array<string, array<string, list<string>|string>>
     */
    private static function normalizeMessages(array $messages): array
    {
        $normalized = [];

        foreach ($messages as $contextKey => $contextTranslations) {
            if (!is_array($contextTranslations)) {
                continue;
            }

            $context = [];

            foreach ($contextTranslations as $originalKey => $value) {
                if (is_string($value)) {
                    $context[(string) $originalKey] = $value;
                    continue;
                }

                if (!is_array($value)) {
                    continue;
                }

                $plurals = [];

                foreach ($value as $pluralValue) {
                    if (is_string($pluralValue)) {
                        $plurals[] = $pluralValue;
                    }
                }

                if ($plurals !== []) {
                    $context[(string) $originalKey] = $plurals;
                }
            }

            if ($context !== []) {
                $normalized[(string) $contextKey] = $context;
            }
        }

        return $normalized;
    }

    /**
     * Set the default domain.
     */
    public function defaultDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function noop(string $original): string
    {
        return $original;
    }

    public function gettext(string $original): string
    {
        return $this->translate(null, null, $original);
    }

    public function ngettext(string $original, string $plural, int $value): string
    {
        return $this->translatePlural(null, null, $original, $plural, $value);
    }

    public function dngettext(string $domain, string $original, string $plural, int $value): string
    {
        return $this->translatePlural($domain, null, $original, $plural, $value);
    }

    public function npgettext(string $context, string $original, string $plural, int $value): string
    {
        return $this->translatePlural(null, $context, $original, $plural, $value);
    }

    public function pgettext(string $context, string $original): string
    {
        return $this->translate(null, $context, $original);
    }

    public function dgettext(string $domain, string $original): string
    {
        return $this->translate($domain, null, $original);
    }

    public function dpgettext(string $domain, string $context, string $original): string
    {
        return $this->translate($domain, $context, $original);
    }

    public function dnpgettext(string $domain, string $context, string $original, string $plural, int $value): string
    {
        return $this->translatePlural($domain, $context, $original, $plural, $value);
    }

    protected function translate(?string $domain, ?string $context, string $original): string
    {
        $translation = $this->getTranslation($domain, $context, $original);

        if (isset($translation[0]) && $translation[0] !== '') {
            return $translation[0];
        }

        return $original;
    }

    protected function translatePlural(
        ?string $domain,
        ?string $context,
        string $original,
        string $plural,
        int $value
    ): string {
        $translation = $this->getTranslation($domain, $context, $original);
        $key = $this->getPluralIndex($domain, $value, $translation === null);

        if (isset($translation[$key]) && $translation[$key] !== '') {
            return $translation[$key];
        }

        return ($key === 0) ? $original : $plural;
    }

    /**
     * Search and returns a translation.
     *
     * @return list<string>|null
     */
    protected function getTranslation(?string $domain, ?string $context, string $original): ?array
    {
        if ($domain === null) {
            $domain = $this->domain ?? '';
        }

        if ($context === null) {
            $context = '';
        }

        $value = $this->dictionary[$domain][$context][$original] ?? null;

        if ($value === null) {
            return null;
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Executes the plural decision code given the number to decide which
     * plural version to take.
     */
    protected function getPluralIndex(?string $domain, int $n, bool $fallback): int
    {
        if ($domain === null) {
            $domain = $this->domain ?? '';
        }

        //Not loaded domain or translation, use a fallback
        if (!isset($this->plurals[$domain]) || $fallback) {
            return $n == 1 ? 0 : 1;
        }

        if (!isset($this->plurals[$domain]['function'])) {
            $function = eval("return function (\$n) { {$this->plurals[$domain]['code']} };");

            if (!$function instanceof Closure) {
                throw new InvalidArgumentException('Invalid plural form expression');
            }

            $this->plurals[$domain]['function'] = $function;
        }

        $result = ($this->plurals[$domain]['function'])($n);

        if (is_bool($result)) {
            return $result ? 1 : 0;
        }

        if (is_int($result)) {
            return $result;
        }

        throw new InvalidArgumentException(sprintf(
            'The plural form expression must return an integer index, %s returned',
            get_debug_type($result)
        ));
    }

    /**
     * This function prepares the gettext plural form expression to be evaluated by PHP
     *
     * Nested ternary IFs will be enclosed by parenthesis and the variable "n" will be prefixed by "$".
     *
     * $n==1 ? 0 : $n%10>=2 && $n%10<=4 && ($n%100<10 || $n%100>=20) ? 1 : 2
     * becomes
     * $n==1 ? 0 : ($n%10>=2 && $n%10<=4 && ($n%100<10 || $n%100>=20) ? 1 : 2)
     */
    private static function fixTerseIfs(string $pluralForms): string
    {
        if (preg_match('/[^<>|%&!?:=n()\d\s]/', $pluralForms)) {
            throw new InvalidArgumentException('Invalid plural form expression');
        }
        $pieces = explode(':', str_replace('n', '$n', $pluralForms));
        $last = array_pop($pieces);
        $pluralForms = '';
        foreach ($pieces as $piece) {
            $pluralForms .= "$piece:(";
        }
        return $pluralForms . $last . str_repeat(')', count($pieces));
    }
}
