<?php

declare(strict_types=1);

namespace Omega\Gettext;

use function array_fill;
use function array_slice;
use function array_values;
use function count;

/**
 * Represents a single translatable string with its translation state.
 *
 * A translation is identified by the combination of context and original
 * string (the id joins them with the gettext EOT separator `\004`). It holds
 * the translated texts (singular and plurals), the previous strings recorded
 * when the source changed, the disabled state (`#~` obsolete entries) and
 * four metadata collections: References, Flags and two Comments sets.
 *
 * Instances are immutable by design: withContext() / withOriginal() return
 * clones, and mergeWith() never mutates its operands.
 *
 * @phpstan-consistent-constructor
 */
class Translation
{
    /** @var string Unique identity: context and original joined by `\004`. */
    protected string $id;

    /** @var string|null Gettext context, or null for the default context. */
    protected ?string $context;

    /** @var string The source string to be translated (msgid). */
    protected string $original;
    /** @var string|null The source plural string (msgid_plural), if any. */
    protected ?string $plural = null;

    /** @var string|null The translated text (msgstr); empty means untranslated. */
    protected ?string $translation = null;

    /** @var list<string> Translations of each plural form (msgstr[1], msgstr[2]...). */
    protected array $pluralTranslations = [];

    /** @var bool True when the entry is obsolete and rendered as `#~`. */
    protected bool $disabled = false;
    /** @var References Source code occurrences of this string. */
    protected References $references;
    /** @var Flags Gettext flags such as `fuzzy` or `php-format`. */
    protected Flags $flags;

    /** @var Comments Notes written by translators (# comments). */
    protected Comments $comments;

    /** @var Comments Notes extracted from the source code (#. comments). */
    protected Comments $extractedComments;
    /** @var string|null Previous context, recorded when the context has changed (#| msgctxt). */
    protected ?string $previousContext = null;

    /** @var string|null Previous original string, recorded after a source change (#| msgid). */
    protected ?string $previousOriginal = null;

    /** @var string|null Previous plural string, recorded after a source change (#| msgid_plural). */
    protected ?string $previousPlural = null;

    /**
     * Creates a new translation.
     *
     * @param string|null $context Gettext context, or null for the default one.
     * @param string $original Source string to translate (msgid).
     * @param string|null $plural Optional source plural string (msgid_plural).
     *
     * @return static The new translation instance.
     */
    public static function create(?string $context, string $original, ?string $plural = null): Translation
    {
        $id = static::generateId($context, $original);

        $translation = new static($id);
        $translation->context = $context;
        $translation->original = $original;

        if (isset($plural)) {
            $translation->setPlural($plural);
        }

        return $translation;
    }

    /**
     * Builds the unique id joining context and original with the EOT byte.
     *
     * @param string|null $context Gettext context, or null for the default one.
     * @param string $original Source string.
     *
     * @return string The resulting identifier.
     */
    protected static function generateId(?string $context, string $original): string
    {
        return "{$context}\004{$original}";
    }

    /**
     * Initializes the metadata collections; use create() instead.
     *
     * @param string $id Pre-built unique identifier of the translation.
     */
    protected function __construct(string $id)
    {
        $this->id = $id;

        $this->references = new References();
        $this->flags = new Flags();
        $this->comments = new Comments();
        $this->extractedComments = new Comments();
    }

    /**
     * Clones the metadata collections so copies never share mutable state.
     */
    public function __clone()
    {
        $this->references = clone $this->references;
        $this->flags = clone $this->flags;
        $this->comments = clone $this->comments;
        $this->extractedComments = clone $this->extractedComments;
    }

    /**
     * Exports every field of the translation, including the metadata collections.
     *
     * @return array<string, mixed> Flat representation of the whole entry.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'context' => $this->context,
            'original' => $this->original,
            'translation' => $this->translation,
            'plural' => $this->plural,
            'pluralTranslations' => $this->pluralTranslations,
            'disabled' => $this->disabled,
            'previousContext' => $this->previousContext,
            'previousOriginal' => $this->previousOriginal,
            'previousPlural' => $this->previousPlural,
            'references' => $this->getReferences()->toArray(),
            'flags' => $this->getFlags()->toArray(),
            'comments' => $this->getComments()->toArray(),
            'extractedComments' => $this->getExtractedComments()->toArray(),
        ];
    }

    /**
     * Returns the unique identifier of this translation.
     *
     * @return string The context and original joined by `\004`.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns the gettext context.
     *
     * @return string|null The context, or null for the default one.
     */
    public function getContext(): ?string
    {
        return $this->context;
    }

    /**
     * Returns a clone with a different context; the id is rebuilt accordingly.
     *
     * @param string|null $context New gettext context, or null for the default one.
     *
     * @return Translation The updated clone; the original instance is untouched.
     */
    public function withContext(?string $context): Translation
    {
        $clone = clone $this;
        $clone->context = $context;
        $clone->id = static::generateId($clone->getContext(), $clone->getOriginal());

        return $clone;
    }

    /**
     * Returns the source string to translate.
     *
     * @return string The original text (msgid).
     */
    public function getOriginal(): string
    {
        return $this->original;
    }

    /**
     * Returns a clone with a different original string; the id is rebuilt accordingly.
     *
     * @param string $original New source string (msgid).
     *
     * @return Translation The updated clone; the original instance is untouched.
     */
    public function withOriginal(string $original): Translation
    {
        $clone = clone $this;
        $clone->original = $original;
        $clone->id = static::generateId($clone->getContext(), $clone->getOriginal());

        return $clone;
    }

    /**
     * Sets the source plural string.
     *
     * @param string $plural Source plural text (msgid_plural).
     *
     * @return self This object, for method chaining.
     */
    public function setPlural(string $plural): self
    {
        $this->plural = $plural;

        return $this;
    }

    /**
     * Returns the source plural string.
     *
     * @return string|null The plural text (msgid_plural), or null if not defined.
     */
    public function getPlural(): ?string
    {
        return $this->plural;
    }

    /**
     * Sets the previous original, recorded when the source has changed.
     *
     * @param string|null $previousOriginal Previous value rendered as `#| msgid`, or null to clear it.
     *
     * @return self This object, for method chaining.
     */
    public function setPreviousOriginal(?string $previousOriginal): self
    {
        $this->previousOriginal = $previousOriginal;

        return $this;
    }

    /**
     * Returns the previous original.
     *
     * @return string|null The previous value rendered as `#| msgid`, or null if none.
     */
    public function getPreviousOriginal(): ?string
    {
        return $this->previousOriginal;
    }

    /**
     * Sets the previous context, recorded when the source has changed.
     *
     * @param string|null $previousContext Previous value rendered as `#| msgctxt`, or null to clear it.
     *
     * @return self This object, for method chaining.
     */
    public function setPreviousContext(?string $previousContext): self
    {
        $this->previousContext = $previousContext;

        return $this;
    }

    /**
     * Returns the previous context.
     *
     * @return string|null The previous value rendered as `#| msgctxt`, or null if none.
     */
    public function getPreviousContext(): ?string
    {
        return $this->previousContext;
    }

    /**
     * Sets the previous plural, recorded when the source has changed.
     *
     * @param string|null $previousPlural Previous value rendered as `#| msgid_plural`, or null to clear it.
     *
     * @return self This object, for method chaining.
     */
    public function setPreviousPlural(?string $previousPlural): self
    {
        $this->previousPlural = $previousPlural;

        return $this;
    }

    /**
     * Returns the previous plural.
     *
     * @return string|null The previous value rendered as `#| msgid_plural`, or null if none.
     */
    public function getPreviousPlural(): ?string
    {
        return $this->previousPlural;
    }

    /**
     * Marks the entry as obsolete (`#~`) or restores it.
     *
     * @param bool $disabled True to disable the entry, false to restore it.
     *
     * @return self This object, for method chaining.
     */
    public function disable(bool $disabled = true): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Checks whether the entry is obsolete.
     *
     * @return bool True when the entry renders as `#~`.
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Sets the translated text.
     *
     * An empty string marks the entry as untranslated.
     *
     * @param string $translation Translated text (msgstr).
     *
     * @return self This object, for method chaining.
     */
    public function translate(string $translation): self
    {
        $this->translation = $translation;

        return $this;
    }

    /**
     * Returns the translated text.
     *
     * @return string|null The translated text (msgstr), or null if untranslated.
     */
    public function getTranslation(): ?string
    {
        return $this->translation;
    }

    /**
     * Checks whether a non-empty translation exists.
     *
     * @return bool True when msgstr is present and not empty.
     */
    public function isTranslated(): bool
    {
        return isset($this->translation) && $this->translation !== '';
    }

    /**
     * Replaces all plural translations at once.
     *
     * Element 0 corresponds to msgstr[1], element 1 to msgstr[2], and so on:
     * msgstr[0] belongs to the singular translation.
     *
     * @param string ...$translations One translated text per plural form.
     *
     * @return self This object, for method chaining.
     */
    public function translatePlural(string ...$translations): self
    {
        $this->pluralTranslations = array_values($translations);

        return $this;
    }

    /**
     * Returns the plural translations, padded or trimmed to the requested size.
     *
     * Missing forms are returned as empty strings so callers can map every
     * plural index safely.
     *
     * @param int|null $size Expected number of plural forms, or null for no adjustment.
     *
     * @return list<string> One translated text per plural form.
     */
    public function getPluralTranslations(?int $size = null): array
    {
        if ($size === null) {
            return $this->pluralTranslations;
        }

        $length = count($this->pluralTranslations);

        if ($size > $length) {
            return $this->pluralTranslations + array_fill(0, $size, '');
        }

        return array_slice($this->pluralTranslations, 0, $size);
    }

    /**
     * Provides write access to the source code occurrences of this string.
     *
     * @return References The mutable metadata collection.
     */
    public function getReferences(): References
    {
        return $this->references;
    }

    /**
     * Provides write access to the gettext flags such as `fuzzy` or `php-format`.
     *
     * @return Flags The mutable metadata collection.
     */
    public function getFlags(): Flags
    {
        return $this->flags;
    }

    /**
     * Provides write access to the translator comments (#).
     *
     * @return Comments The mutable metadata collection.
     */
    public function getComments(): Comments
    {
        return $this->comments;
    }

    /**
     * Provides write access to the extracted comments from the source code (#.).
     *
     * @return Comments The mutable metadata collection.
     */
    public function getExtractedComments(): Comments
    {
        return $this->extractedComments;
    }

    /**
     * Merges another translation into this one without mutating any operand.
     *
     * Without a strategy it performs a union merge: local values win, missing
     * ones are imported. Use the Merge:: constants to keep or replace each
     * group of fields independently.
     *
     * @param Translation $translation The instance whose values are merged in.
     * @param int $strategy Bit mask of Merge:: flags controlling every field family.
     *
     * @return Translation A new instance holding the merged result.
     */
    public function mergeWith(Translation $translation, int $strategy = 0): Translation
    {
        $merged = clone $this;

        if ($strategy & Merge::COMMENTS_THEIRS) {
            $merged->comments = clone $translation->comments;
        } elseif (!($strategy & Merge::COMMENTS_OURS)) {
            $merged->comments = $merged->comments->mergeWith($translation->comments);
        }

        if ($strategy & Merge::EXTRACTED_COMMENTS_THEIRS) {
            $merged->extractedComments = clone $translation->extractedComments;
        } elseif (!($strategy & Merge::EXTRACTED_COMMENTS_OURS)) {
            $merged->extractedComments = $merged->extractedComments->mergeWith($translation->extractedComments);
        }

        if ($strategy & Merge::REFERENCES_THEIRS) {
            $merged->references = clone $translation->references;
        } elseif (!($strategy & Merge::REFERENCES_OURS)) {
            $merged->references = $merged->references->mergeWith($translation->references);
        }

        if ($strategy & Merge::FLAGS_THEIRS) {
            $merged->flags = clone $translation->flags;
        } elseif (!($strategy & Merge::FLAGS_OURS)) {
            $merged->flags = $merged->flags->mergeWith($translation->flags);
        }

        $override = (bool) ($strategy & Merge::TRANSLATIONS_OVERRIDE);

        if (!$merged->translation || ($translation->translation && $override)) {
            $merged->translation = $translation->translation;
        }

        if (!$merged->plural || ($translation->plural && $override)) {
            $merged->plural = $translation->plural;
        }

        if (!$merged->previousContext || ($translation->previousContext && $override)) {
            $merged->previousContext = $translation->previousContext;
        }

        if (!$merged->previousOriginal || ($translation->previousOriginal && $override)) {
            $merged->previousOriginal = $translation->previousOriginal;
        }

        if (!$merged->previousPlural || ($translation->previousPlural && $override)) {
            $merged->previousPlural = $translation->previousPlural;
        }

        if (empty($merged->pluralTranslations) || (!empty($translation->pluralTranslations) && $override)) {
            $merged->pluralTranslations = $translation->pluralTranslations;
        }

        $merged->disable($translation->isDisabled());

        return $merged;
    }
}
