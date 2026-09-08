<?php

/**
 * Exhaustively walks the strategy/state matrix of Translation::mergeWith().
 *
 * The method has four independent metadata families, each with three arms
 * (theirs / ours / union), six value fields driven by presence bits plus an
 * override flag, and a disabled propagation. Data providers enumerate that
 * matrix programmatically so every branch outcome tuple gets exercised,
 * mirroring the approach used by Languages/RulesTest for CLDR data.
 */

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Merge;
use Omega\Gettext\References;
use Omega\Gettext\Translation;

covers(Comments::class);
covers(Flags::class);
covers(Merge::class);
covers(References::class);
covers(Translation::class);

dataset('mergeMatrix', function (): array {
    $families = [
        [Merge::COMMENTS_THEIRS, Merge::COMMENTS_OURS],
        [Merge::EXTRACTED_COMMENTS_THEIRS, Merge::EXTRACTED_COMMENTS_OURS],
        [Merge::REFERENCES_THEIRS, Merge::REFERENCES_OURS],
        [Merge::FLAGS_THEIRS, Merge::FLAGS_OURS],
    ];

    $rows = [];

    foreach ([0, 1, 2] as $commentsArm) {
        foreach ([0, 1, 2] as $extractedArm) {
            foreach ([0, 1, 2] as $referencesArm) {
                foreach ([0, 1, 2] as $flagsArm) {
                    $strategy = 0;

                    foreach ([$commentsArm, $extractedArm, $referencesArm, $flagsArm] as $index => $arm) {
                        $strategy |= match ($arm) {
                            1 => $families[$index][0],
                            2 => $families[$index][1],
                            default => 0,
                        };
                    }

                    // Every 6-bit vector: ours holds the complement so the
                    // merge takes theirs exactly where the bit is set.
                    foreach ([0, 0x2A, 0x15, 0x3F] as $theirsMask) {
                        foreach ([false, true] as $theirsDisabled) {
                            // Override on/off completes the field rules:
                            // theirs wins only where present AND override.
                            foreach ([0, Merge::TRANSLATIONS_OVERRIDE] as $overrideBit) {
                                $rows[] = [$strategy | $overrideBit, $theirsMask, $theirsDisabled];
                            }
                        }
                    }
                }
            }
        }
    }

    return $rows;
});

it('merges every family and state combination', function (int $strategy, int $theirsMask, bool $theirsDisabled): void {
    $override = (bool) ($strategy & Merge::TRANSLATIONS_OVERRIDE);

    $ours = Translation::create('ctx-ours', 'original-ours');
    $ours->translation = 'translation-ours';
    $ours->plural = 'plural-ours';
    $ours->previousContext = 'prevctx-ours';
    $ours->previousOriginal = 'prevorig-ours';
    $ours->previousPlural = 'prevplur-ours';
    $ours->translatePlural('ptrans-ours');
    $ours->getComments()->add('comment-ours');
    $ours->getExtractedComments()->add('extracted-ours');
    $ours->getReferences()->add('ours.php', 1);
    $ours->getFlags()->add('flag-ours');

    $theirs = Translation::create(null, 'original-theirs');
    $theirs->plural = 'plural-theirs';

    if (($theirsMask & 0x01) !== 0) {
        $theirs->translation = 'translation-theirs';
    }

    if (($theirsMask & 0x02) !== 0) {
        $theirs->previousContext = 'prevctx-theirs';
    }

    if (($theirsMask & 0x04) !== 0) {
        $theirs->previousOriginal = 'prevorig-theirs';
    }

    if (($theirsMask & 0x08) !== 0) {
        $theirs->previousPlural = 'prevplur-theirs';
    }

    if (($theirsMask & 0x10) !== 0) {
        $theirs->translatePlural('ptrans-theirs');
    }

    $theirs->getComments()->add('comment-theirs');
    $theirs->getExtractedComments()->add('extracted-theirs');
    $theirs->getReferences()->add('theirs.php', 2);
    $theirs->getFlags()->add('flag-theirs');
    $theirs->disabled = $theirsDisabled;

    // Ours keeps the complementary presence bits: where the theirs mask
    // is unset and override is off, the local value must survive.
    $merged = $ours->mergeWith($theirs, $strategy);

    assertFamily(
        $merged->getComments()->toArray(),
        ['comment-ours'],
        ['comment-theirs'],
        $strategy & Merge::COMMENTS_THEIRS,
        $strategy & Merge::COMMENTS_OURS
    );
    assertFamily(
        $merged->getExtractedComments()->toArray(),
        ['extracted-ours'],
        ['extracted-theirs'],
        $strategy & Merge::EXTRACTED_COMMENTS_THEIRS,
        $strategy & Merge::EXTRACTED_COMMENTS_OURS
    );
    assertReferenceFamily(
        $merged->getReferences()->toArray(),
        $strategy & Merge::REFERENCES_THEIRS,
        $strategy & Merge::REFERENCES_OURS
    );
    assertFamily(
        $merged->getFlags()->toArray(),
        ['flag-ours'],
        ['flag-theirs'],
        $strategy & Merge::FLAGS_THEIRS,
        $strategy & Merge::FLAGS_OURS
    );

    $expectTheirs = static function (bool $oursPresent, bool $theirsPresent) use ($override): bool {
        return !$oursPresent || ($theirsPresent && $override);
    };

    $takeTranslation = $expectTheirs(true, ($theirsMask & 0x01) !== 0);
    expect($merged->translation)
        ->toBe($takeTranslation ? 'translation-theirs' : 'translation-ours');

    $takePlural = $expectTheirs(true, true);
    expect($merged->plural)->toBe($takePlural ? 'plural-theirs' : 'plural-ours');

    expect($merged->previousContext)
        ->toBe($expectTheirs(true, ($theirsMask & 0x02) !== 0) ? 'prevctx-theirs' : 'prevctx-ours');
    expect($merged->previousOriginal)
        ->toBe($expectTheirs(true, ($theirsMask & 0x04) !== 0) ? 'prevorig-theirs' : 'prevorig-ours');
    expect($merged->previousPlural)
        ->toBe($expectTheirs(true, ($theirsMask & 0x08) !== 0) ? 'prevplur-theirs' : 'prevplur-ours');
    expect($merged->getPluralTranslations())
        ->toBe($expectTheirs(true, ($theirsMask & 0x10) !== 0) ? ['ptrans-theirs'] : ['ptrans-ours']);

    expect($merged->disabled)->toBe($theirsDisabled);
})->with('mergeMatrix');

/**
 * @param list<string> $actual
 * @param list<string> $oursItems
 * @param list<string> $theirsItems
 */
function assertFamily(
    array $actual,
    array $oursItems,
    array $theirsItems,
    int $theirsFlag,
    int $oursFlag
): void {
    if ($theirsFlag !== 0) {
        assertSameCanonicalize($theirsItems, $actual);
    } elseif ($oursFlag !== 0) {
        assertSameCanonicalize($oursItems, $actual);
    } else {
        assertSameCanonicalize([...$oursItems, ...$theirsItems], $actual);
    }
}

/**
 * @param array<string, list<int>> $actual
 */
function assertReferenceFamily(array $actual, int $theirsFlag, int $oursFlag): void
{
    if ($theirsFlag !== 0) {
        expect($actual)->toBe(['theirs.php' => [2]]);
    } elseif ($oursFlag !== 0) {
        expect($actual)->toBe(['ours.php' => [1]]);
    } else {
        expect($actual)->toBe(['ours.php' => [1], 'theirs.php' => [2]]);
    }
}

/**
 * Collection order differs between union sources, so comparison ignores it.
 *
 * @param list<string> $expected
 * @param list<string> $actual
 */
function assertSameCanonicalize(array $expected, array $actual): void
{
    sort($expected);
    sort($actual);

    expect($expected)->toBe($actual);
}
