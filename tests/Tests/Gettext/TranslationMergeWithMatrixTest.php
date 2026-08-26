<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Merge;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Exhaustively walks the strategy/state matrix of Translation::mergeWith().
 *
 * The method has four independent metadata families, each with three arms
 * (theirs / ours / union), six value fields driven by presence bits plus an
 * override flag, and a disabled propagation. Data providers enumerate that
 * matrix programmatically so every branch outcome tuple gets exercised,
 * mirroring the approach used by Languages/RulesTest for CLDR data.
 */
#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Merge::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
class TranslationMergeWithMatrixTest extends TestCase
{
    private const FIELDS = ['translation', 'plural', 'previousContext', 'previousOriginal', 'previousPlural', 'pluralTranslations'];

    /**
     * Builds every combination of family arms (union/theirs/ours) crossed
     * with the override flag, every field-presence outcome vector and the
     * disabled propagation.
     *
     * @return list<array{int, int, bool}>
     */
    public static function mergeMatrixProvider(): array
    {
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
    }

    #[DataProvider('mergeMatrixProvider')]
    public function testMergeMatrix(int $strategy, int $theirsMask, bool $theirsDisabled): void
    {
        $override = (bool) ($strategy & Merge::TRANSLATIONS_OVERRIDE);

        $ours = Translation::create('ctx-ours', 'original-ours');
        $ours->translate('translation-ours');
        $ours->setPlural('plural-ours');
        $ours->setPreviousContext('prevctx-ours');
        $ours->setPreviousOriginal('prevorig-ours');
        $ours->setPreviousPlural('prevplur-ours');
        $ours->translatePlural('ptrans-ours');
        $ours->getComments()->add('comment-ours');
        $ours->getExtractedComments()->add('extracted-ours');
        $ours->getReferences()->add('ours.php', 1);
        $ours->getFlags()->add('flag-ours');

        $theirs = Translation::create(null, 'original-theirs');
        $theirs->setPlural('plural-theirs');

        if (($theirsMask & 0x01) !== 0) {
            $theirs->translate('translation-theirs');
        }

        if (($theirsMask & 0x02) !== 0) {
            $theirs->setPreviousContext('prevctx-theirs');
        }

        if (($theirsMask & 0x04) !== 0) {
            $theirs->setPreviousOriginal('prevorig-theirs');
        }

        if (($theirsMask & 0x08) !== 0) {
            $theirs->setPreviousPlural('prevplur-theirs');
        }

        if (($theirsMask & 0x10) !== 0) {
            $theirs->translatePlural('ptrans-theirs');
        }

        $theirs->getComments()->add('comment-theirs');
        $theirs->getExtractedComments()->add('extracted-theirs');
        $theirs->getReferences()->add('theirs.php', 2);
        $theirs->getFlags()->add('flag-theirs');
        $theirs->disable($theirsDisabled);

        // Ours keeps the complementary presence bits: where the theirs mask
        // is unset and override is off, the local value must survive.
        $merged = $ours->mergeWith($theirs, $strategy);

        $this->assertFamily(
            $merged->getComments()->toArray(),
            ['comment-ours'],
            ['comment-theirs'],
            $strategy & Merge::COMMENTS_THEIRS,
            $strategy & Merge::COMMENTS_OURS
        );
        $this->assertFamily(
            $merged->getExtractedComments()->toArray(),
            ['extracted-ours'],
            ['extracted-theirs'],
            $strategy & Merge::EXTRACTED_COMMENTS_THEIRS,
            $strategy & Merge::EXTRACTED_COMMENTS_OURS
        );
        $this->assertReferenceFamily(
            $merged->getReferences()->toArray(),
            $strategy & Merge::REFERENCES_THEIRS,
            $strategy & Merge::REFERENCES_OURS
        );
        $this->assertFamily(
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
        $this->assertSame(
            $takeTranslation ? 'translation-theirs' : 'translation-ours',
            $merged->getTranslation()
        );

        $takePlural = $expectTheirs(true, true);
        $this->assertSame($takePlural ? 'plural-theirs' : 'plural-ours', $merged->getPlural());

        $this->assertSame(
            $expectTheirs(true, ($theirsMask & 0x02) !== 0) ? 'prevctx-theirs' : 'prevctx-ours',
            $merged->getPreviousContext()
        );
        $this->assertSame(
            $expectTheirs(true, ($theirsMask & 0x04) !== 0) ? 'prevorig-theirs' : 'prevorig-ours',
            $merged->getPreviousOriginal()
        );
        $this->assertSame(
            $expectTheirs(true, ($theirsMask & 0x08) !== 0) ? 'prevplur-theirs' : 'prevplur-ours',
            $merged->getPreviousPlural()
        );
        $this->assertSame(
            $expectTheirs(true, ($theirsMask & 0x10) !== 0) ? ['ptrans-theirs'] : ['ptrans-ours'],
            $merged->getPluralTranslations()
        );

        $this->assertSame($theirsDisabled, $merged->isDisabled());
    }

    /**
     * @param list<string> $actual
     * @param list<string> $oursItems
     * @param list<string> $theirsItems
     */
    private function assertFamily(
        array $actual,
        array $oursItems,
        array $theirsItems,
        int $theirsFlag,
        int $oursFlag
    ): void {
        if ($theirsFlag !== 0) {
            $this->assertSameCanonicalize($theirsItems, $actual);
        } elseif ($oursFlag !== 0) {
            $this->assertSameCanonicalize($oursItems, $actual);
        } else {
            $this->assertSameCanonicalize([...$oursItems, ...$theirsItems], $actual);
        }
    }

    /**
     * @param array<string, list<int>> $actual
     */
    private function assertReferenceFamily(array $actual, int $theirsFlag, int $oursFlag): void
    {
        if ($theirsFlag !== 0) {
            $this->assertSame(['theirs.php' => [2]], $actual);
        } elseif ($oursFlag !== 0) {
            $this->assertSame(['ours.php' => [1]], $actual);
        } else {
            $this->assertSame(['ours.php' => [1], 'theirs.php' => [2]], $actual);
        }
    }

    /**
     * Collection order differs between union sources, so comparison ignores it.
     *
     * @param list<string> $expected
     * @param list<string> $actual
     */
    private function assertSameCanonicalize(array $expected, array $actual): void
    {
        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);
    }
}
