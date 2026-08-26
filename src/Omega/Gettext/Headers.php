<?php

declare(strict_types=1);

namespace Omega\Gettext;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;

use function count;
use function intval;
use function is_array;
use function is_string;
use function ksort;
use function preg_match;
use function sprintf;
use function str_replace;
use function trim;

/**
 * Stores the .po file headers of a Translations collection.
 *
 * Headers are key/value pairs rendered alphabetically inside the empty
 * `msgid` entry (msgid ""), such as `Language`, `Plural-Forms`, or any
 * custom `X-` field. Well-known names are exposed as HEADER_* constants.
 *
 * @phpstan-consistent-constructor
 *
 * @implements IteratorAggregate<string, string>
 */
class Headers implements JsonSerializable, Countable, IteratorAggregate
{
    /** @var string Header holding the ISO code of the translation language. */
    public const string HEADER_LANGUAGE = 'Language';

    /** @var string Header holding the plural rules (`nplurals` and `plural`). */
    public const string HEADER_PLURAL = 'Plural-Forms';

    /** @var string Non-standard header holding the gettext domain name. */
    public const string HEADER_DOMAIN = 'X-Domain';

    /** @var array<string, string> Header names mapped to their trimmed values. */
    protected array $headers = [];

    /**
     * Rebuilds an instance from a var_export() export; non-string values are skipped.
     *
     * @param array<string, mixed> $state Exported state, holding a `headers` array.
     *
     * @return static The reconstructed instance.
     */
    public static function __set_state(array $state): Headers
    {
        $stateHeaders = $state['headers'] ?? [];
        $headers = [];

        if (is_array($stateHeaders)) {
            foreach ($stateHeaders as $name => $value) {
                if (is_string($value)) {
                    $headers[(string) $name] = $value;
                }
            }
        }

        return new static($headers);
    }

    /**
     * Initializes the headers; keys are kept sorted alphabetically.
     *
     * @param array<string, string> $headers Initial header names and values.
     */
    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
        ksort($this->headers);
    }

    /**
     * Renders the headers instead of the private properties in var_dump().
     *
     * @return array<string, string> The current headers.
     */
    public function __debugInfo()
    {
        return $this->toArray();
    }

    /**
     * Sets a header value, trimming surrounding whitespace, and re-sorts.
     *
     * @param string $name Header name, e.g. `Project-Id-Version`.
     * @param string $value Header value.
     *
     * @return self This object, for method chaining.
     */
    public function set(string $name, string $value): self
    {
        $this->headers[$name] = trim($value);
        ksort($this->headers);

        return $this;
    }

    /**
     * Returns the value of a header.
     *
     * @param string $name Header name to look up.
     *
     * @return string|null The trimmed value, or null if the header is not set.
     */
    public function get(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Removes a header; unknown names are ignored.
     *
     * @param string $name Header name to remove.
     *
     * @return self This object, for method chaining.
     */
    public function delete(string $name): self
    {
        unset($this->headers[$name]);

        return $this;
    }

    /**
     * Removes every header.
     *
     * @return self This object, for method chaining.
     */
    public function clear(): self
    {
        $this->headers = [];

        return $this;
    }

    /**
     * Serializes the headers for json_encode().
     *
     * @return array<string, string> The current headers sorted by name.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Iterates over the header names and values.
     *
     * @return ArrayIterator<string, string> Iterator of name/value pairs.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * Counts the stored headers.
     *
     * @return int Number of headers currently set.
     */
    public function count(): int
    {
        return count($this->headers);
    }

    /**
     * Sets the language of the translation.
     *
     * @param string $language ISO language code, e.g. `gl` or `en_US`.
     *
     * @return self This object, for method chaining.
     */
    public function setLanguage(string $language): self
    {
        return $this->set(self::HEADER_LANGUAGE, $language);
    }

    /**
     * Returns the language of the translation.
     *
     * @return string|null The ISO language code, or null if not set.
     */
    public function getLanguage(): ?string
    {
        return $this->get(self::HEADER_LANGUAGE);
    }

    /**
     * Sets the gettext domain name in the non-standard X-Domain header.
     *
     * @param string $domain Domain name, e.g. `messages`.
     *
     * @return self This object, for method chaining.
     */
    public function setDomain(string $domain): self
    {
        return $this->set(self::HEADER_DOMAIN, $domain);
    }

    /**
     * Returns the gettext domain name.
     *
     * @return string|null The domain name, or null if not set.
     */
    public function getDomain(): ?string
    {
        return $this->get(self::HEADER_DOMAIN);
    }

    /**
     * Builds and sets the Plural-Forms header from its components.
     *
     * @param int $count Number of plural forms (`nplurals`).
     * @param string $rule C-like boolean expression using the variable `n`.
     *
     * @throws InvalidArgumentException If the rule contains letters other than `n`,
     *         because only digits and comparison/logic operators are allowed.
     *
     * @return self This object, for method chaining.
     */
    public function setPluralForm(int $count, string $rule): self
    {
        if (preg_match('/[a-z]/i', str_replace('n', '', $rule))) {
            throw new InvalidArgumentException(sprintf('Invalid Plural form: "%s"', $rule));
        }

        return $this->set(self::HEADER_PLURAL, sprintf('nplurals=%d; plural=%s;', $count, $rule));
    }

    /**
     * Parses the Plural-Forms header into its components.
     *
     * @return array{int, string}|null A [count, rule] pair, or null when the
     *         header is missing or malformed.
     */
    public function getPluralForm(): ?array
    {
        $header = $this->get(self::HEADER_PLURAL);

        if (
            !empty($header) &&
            preg_match('/^nplurals\s*=\s*(\d+)\s*;\s*plural\s*=\s*([^;]+)\s*;$/', $header, $matches)
        ) {
            return [intval($matches[1]), $matches[2]];
        }

        return null;
    }

    /**
     * Exports the headers as a plain array.
     *
     * @return array<string, string> Header names mapped to their values.
     */
    public function toArray(): array
    {
        return $this->headers;
    }

    /**
     * Merges another set of headers without mutating this instance: existing
     * names win over the imported ones.
     *
     * @param Headers $headers Headers to import for missing names.
     *
     * @return Headers A clone containing the combined headers.
     */
    public function mergeWith(Headers $headers): Headers
    {
        $merged = clone $this;
        $merged->headers = $headers->headers + $merged->headers;
        ksort($merged->headers);

        return $merged;
    }
}
