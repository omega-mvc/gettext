<?php

declare(strict_types=1);

namespace Omega\Gettext;

/**
 * Bit flags configuring how two Translations or Translation instances are
 * combined by their mergeWith() methods.
 *
 * Every constant belongs to one of these families:
 *
 * - `*_OURS` keeps the value held by the instance mergeWith() is called on;
 * - `*_THEIRS` replaces that value with the one coming from the argument;
 * - `TRANSLATIONS_OVERRIDE` / `HEADERS_OVERRIDE` fill empty fields and
 *   overwrite existing ones with the values of the argument.
 *
 * Constants from different families can be combined with the bitwise OR
 * operator. When no flag is passed, mergeWith() performs a union merge:
 * values missing locally are imported from theirs, existing ones survive.
 */
final class Merge
{
    /** @var int Restricts the result to the translations of the current instance. */
    public const int TRANSLATIONS_OURS = 1 << 0;

    /** @var int Restricts the result to the translations present in the merged instance. */
    public const int TRANSLATIONS_THEIRS = 1 << 1;

    /** @var int Overwrites local translation texts with the non-empty ones of the merged instance. */
    public const int TRANSLATIONS_OVERRIDE = 1 << 2;

    /** @var int Keeps the headers of the current instance untouched. */
    public const int HEADERS_OURS = 1 << 3;

    /** @var int Replaces every header with the ones of the merged instance. */
    public const int HEADERS_THEIRS = 1 << 4;

    /** @var int Fills missing headers and overwrites existing ones with the merged values. */
    public const int HEADERS_OVERRIDE = 1 << 5;

    /** @var int Keeps the comments of the current instance untouched. */
    public const int COMMENTS_OURS = 1 << 6;

    /** @var int Replaces the comments with those of the merged instance. */
    public const int COMMENTS_THEIRS = 1 << 7;

    /** @var int Keeps the extracted comments of the current instance untouched. */
    public const int EXTRACTED_COMMENTS_OURS = 1 << 8;

    /** @var int Replaces the extracted comments with those of the merged instance. */
    public const int EXTRACTED_COMMENTS_THEIRS = 1 << 9;

    /** @var int Keeps the flags of the current instance untouched. */
    public const int FLAGS_OURS = 1 << 10;

    /** @var int Replaces the flags with those of the merged instance. */
    public const int FLAGS_THEIRS = 1 << 11;

    /** @var int Keeps the source references of the current instance untouched. */
    public const int REFERENCES_OURS = 1 << 12;

    /** @var int Replaces the source references with those of the merged instance. */
    public const int REFERENCES_THEIRS = 1 << 13;

    /**
     * Preset strategy for the typical "scan sources, then load the .po file"
     * workflow: scanned data wins on texts and references, while curated
     * metadata (headers, comments, flags) comes from the loaded .po file.
     */
    public const int SCAN_AND_LOAD =
        Merge::HEADERS_OVERRIDE
        | Merge::TRANSLATIONS_OURS
        | Merge::TRANSLATIONS_OVERRIDE
        | Merge::EXTRACTED_COMMENTS_OURS
        | Merge::REFERENCES_OURS
        | Merge::FLAGS_THEIRS
        | Merge::COMMENTS_THEIRS;
}
