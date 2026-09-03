<?php

declare(strict_types=1);

namespace Omega\Gettext;

use ArrayIterator;
use Countable;
use Omega\Gettext\Languages\Language;
use InvalidArgumentException;
use IteratorAggregate;

use function array_intersect_key;
use function array_map;
use function array_values;
use function count;
use function sprintf;

/**
 * Collects every Translation belonging to the same gettext domain.
 *
 * It behaves as a map keyed by translation id (context + original), owns the
 * Headers and collection-level Flags of the domain and provides mergeWith()
 * to combine two catalogs under Merge:: strategy flags.
 *
 * Iterating yields the Translation instances in insertion order.
 *
 * @phpstan-consistent-constructor
 *
 * @implements IteratorAggregate<string, Translation>
 */
class Translations implements Countable, IteratorAggregate
{
    public ?string $description = null {
        get => $this->description;
        set { $this->description = $value; }
    }

    /** @var array<string, Translation> Entries indexed by their unique id. */
    protected array $translations = [];

    /** @var Headers .po headers of this domain, including X-Domain. */
    protected Headers $headers;

    /** @var Flags Collection-level flags applying to the whole catalog. */
    protected Flags $flags;

    /**
     * Creates an empty catalog for a given domain and language.
     *
     * The language is validated against CLDR data and initializes both the
     * Language header and the Plural-Forms rules.
     *
     * @param string|null $domain Gettext domain name, or null to leave it unset.
     * @param string|null $language ISO language code, or null to leave it unset.
     *
     * @return static The new, empty catalog.
     */
    public static function create(?string $domain = null, ?string $language = null): Translations
    {
        $translations = new static();

        if (isset($domain)) {
            $translations->setDomain($domain);
        }

        if (isset($language)) {
            $translations->setLanguage($language);
        }

        return $translations;
    }

    /**
     * Initializes empty headers and flags; use create() instead.
     */
    protected function __construct()
    {
        $this->headers = new Headers();
        $this->flags = new Flags();
    }

    /**
     * Clones every entry and the headers so copies never share mutable state.
     */
    public function __clone()
    {
        foreach ($this->translations as $id => $translation) {
            $this->translations[$id] = clone $translation;
        }

        $this->headers = clone $this->headers;
    }

    /**
     * Provides write access to the collection-level flags.
     *
     * @return Flags The mutable flags collection.
     */
    public function getFlags(): Flags
    {
        return $this->flags;
    }

    /**
     * Exports the whole catalog: description, headers, flags and entries.
     *
     * @return array<string, mixed> Flat representation of the catalog.
     */
    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'headers' => $this->headers->toArray(),
            'flags' => $this->flags->toArray(),
            'translations' => array_map(
                function (Translation $translation) {
                    return $translation->toArray();
                },
                array_values($this->translations)
            ),
        ];
    }

    /**
     * Iterates over the translations in insertion order.
     *
     * @return ArrayIterator<string, Translation> Iterator of ids and entries.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->translations);
    }

    /**
     * Exports all the entries as a plain map.
     *
     * @return array<string, Translation> Entries indexed by their unique id.
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }

    /**
     * Counts the stored translations.
     *
     * @return int Number of entries in the catalog.
     */
    public function count(): int
    {
        return count($this->translations);
    }

    /**
     * Provides access to the .po headers of the domain.
     *
     * @return Headers The mutable headers collection.
     */
    public function getHeaders(): Headers
    {
        return $this->headers;
    }

    /**
     * Inserts a translation, replacing any previous entry with the same id.
     *
     * @param Translation $translation Entry to insert.
     *
     * @return self This object, for method chaining.
     */
    public function add(Translation $translation): self
    {
        $id = $translation->getId();

        $this->translations[$id] = $translation;

        return $this;
    }

    /**
     * Inserts a translation, merging it with any existing one sharing its id.
     *
     * @param Translation $translation Entry to insert or merge.
     * @param int $mergeStrategy Bit mask of Merge:: flags applied on conflict.
     *
     * @return Translation The stored instance: either the inserted entry or the merged result.
     */
    public function addOrMerge(Translation $translation, int $mergeStrategy = 0): Translation
    {
        $id = $translation->getId();

        if (isset($this->translations[$id])) {
            return $this->translations[$id] = $this->translations[$id]->mergeWith($translation, $mergeStrategy);
        }

        return $this->translations[$id] = $translation;
    }

    /**
     * Removes the entry matching the id of the given translation; unknown ones are ignored.
     *
     * @param Translation $translation Entry whose id identifies the row to drop.
     *
     * @return self This object, for method chaining.
     */
    public function remove(Translation $translation): self
    {
        unset($this->translations[$translation->getId()]);

        return $this;
    }

    /**
     * Sets the gettext domain name in the X-Domain header.
     *
     * @param string $domain Domain name, e.g. `messages`.
     *
     * @return self This object, for method chaining.
     */
    public function setDomain(string $domain): self
    {
        $this->getHeaders()->setDomain($domain);

        return $this;
    }

    /**
     * Returns the gettext domain name.
     *
     * @return string|null The domain name, or null if not set.
     */
    public function getDomain(): ?string
    {
        return $this->getHeaders()->getDomain();
    }

    /**
     * Sets the language and derives nplurals + plural rule from CLDR data.
     *
     * @param string $language ISO language code, e.g. `gl` or `pt_BR`.
     *
     * @throws InvalidArgumentException If the code is not found in the CLDR database.
     *
     * @return self This object, for method chaining.
     */
    public function setLanguage(string $language): self
    {
        $info = Language::getById($language);

        if (empty($info)) {
            throw new InvalidArgumentException(sprintf('The language "%s" is not valid', $language));
        }

        $this->getHeaders()
            ->setLanguage($language)
            ->setPluralForm(count($info->categories), $info->formula);

        return $this;
    }

    /**
     * Returns the language of the catalog.
     *
     * @return string|null The ISO language code, or null if not set.
     */
    public function getLanguage(): ?string
    {
        return $this->getHeaders()->getLanguage();
    }

    /**
     * Looks up an entry by context and original string.
     *
     * @param string|null $context Gettext context, or null for the default one.
     * @param string $original Source string to search (msgid).
     *
     * @return Translation|null The matching entry, or null when not found.
     */
    public function find(?string $context, string $original): ?Translation
    {
        return $this->translations[(Translation::create($context, $original))->getId()] ?? null;
    }

    /**
     * Checks whether an entry with the same id is already stored.
     *
     * @param Translation $translation Entry to look up by id.
     *
     * @return bool True if such an entry exists.
     */
    public function has(Translation $translation): bool
    {
        return isset($this->translations[$translation->getId()]);
    }

    /**
     * Merges another catalog into this one without mutating any operand.
     *
     * Without a strategy it performs a union merge: local values win, missing
     * ones are imported. Use the Merge:: constants to restrict which entries
     * survive and how headers, flags and metadata are combined.
     *
     * @param Translations $translations Catalog whose values are merged in.
     * @param int $strategy Bit mask of Merge:: flags controlling every field family.
     *
     * @return Translations A new instance holding the merged result.
     */
    public function mergeWith(Translations $translations, int $strategy = 0): Translations
    {
        $merged = clone $this;

        if ($strategy & Merge::HEADERS_THEIRS) {
            $merged->headers = clone $translations->headers;
        } elseif (!($strategy & Merge::HEADERS_OURS)) {
            $merged->headers = $merged->headers->mergeWith($translations->headers);
        }

        if ($strategy & Merge::FLAGS_THEIRS) {
            $merged->flags = clone $translations->flags;
        } elseif (!($strategy & Merge::FLAGS_OURS)) {
            $merged->flags = $merged->flags->mergeWith($translations->flags);
        }

        if (!$merged->description) {
            $merged->description = $translations->description;
        }

        foreach ($translations as $id => $translation) {
            if (isset($merged->translations[$id])) {
                $translation = $merged->translations[$id]->mergeWith($translation, $strategy);
            }

            $merged->add($translation);
        }

        if ($strategy & Merge::TRANSLATIONS_THEIRS) {
            $merged->translations = array_intersect_key($merged->translations, $translations->translations);
        } elseif ($strategy & Merge::TRANSLATIONS_OURS) {
            $merged->translations = array_intersect_key($merged->translations, $this->translations);
        }

        return $merged;
    }
}
