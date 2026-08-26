<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Merge;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
class TranslationTest extends TestCase
{
    public function testTranslation(): void
    {
        $translation = Translation::create('foo', 'bar');

        $this->assertSame('foo', $translation->getContext());
        $this->assertSame('bar', $translation->getOriginal());
        $this->assertSame("foo\004bar", $translation->getId());
        $this->assertFalse($translation->isTranslated());

        $translation->translation = 'This is the translation';
        $this->assertSame('This is the translation', $translation->translation);
        $this->assertTrue($translation->isTranslated());

        $translation->plural = 'bars';
        $this->assertSame('bars', $translation->plural);

        $translation->translatePlural('bars-1', 'bars-2');
        $this->assertSame(['bars-1', 'bars-2'], $translation->getPluralTranslations());

        $this->assertFalse($translation->disabled);

        $translation->disabled = true;
        $this->assertTrue($translation->disabled);

        $translation->disabled = false;
        $this->assertFalse($translation->disabled);

        $this->assertInstanceOf(Comments::class, $translation->getComments());
        $this->assertInstanceOf(Comments::class, $translation->getExtractedComments());
        $this->assertInstanceOf(Flags::class, $translation->getFlags());
        $this->assertInstanceOf(References::class, $translation->getReferences());

        $clone = clone $translation;

        $this->assertInstanceOf(Comments::class, $clone->getComments());
        $this->assertInstanceOf(Comments::class, $clone->getExtractedComments());
        $this->assertInstanceOf(Flags::class, $clone->getFlags());
        $this->assertInstanceOf(References::class, $clone->getReferences());

        $this->assertNotSame($translation->getComments(), $clone->getComments());
        $this->assertNotSame($translation->getExtractedComments(), $clone->getExtractedComments());
        $this->assertNotSame($translation->getFlags(), $clone->getFlags());
        $this->assertNotSame($translation->getReferences(), $clone->getReferences());
    }

    public function testCreateWithPlural(): void
    {
        $translation = Translation::create('comments', 'One comment', '%s comments');

        $this->assertSame('comments', $translation->getContext());
        $this->assertSame('One comment', $translation->getOriginal());
        $this->assertSame('%s comments', $translation->plural);
        $this->assertSame("comments\004One comment", $translation->getId());

        $translation = Translation::create(null, 'Original');

        $this->assertNull($translation->plural);
    }

    public function testMergeTranslation(): void
    {
        $translation1 = Translation::create('context', 'Original');
        $translation1->translation = 'Orixinal';
        $translation1->getFlags()->add('flag-1', 'flag-2');
        $translation1->getComments()->add('Comment 1', 'Comment 2');
        $translation1->getExtractedComments()->add('Extracted 1');
        $translation1->getReferences()->add('template.php', 34);

        $translation2 = Translation::create('context2', 'Original2');
        $translation2->plural = 'Plural';
        $translation2->translatePlural('Plural 1', 'Plural 2');
        $translation2->getFlags()->add('flag-1', 'flag-3');
        $translation2->getComments()->add('Comment 2', 'Comment 3');
        $translation2->getReferences()
            ->add('template.php', 44)
            ->add('template2.php', 55);

        $merged = $translation1->mergeWith($translation2);

        $this->assertSame('context', $merged->getContext());
        $this->assertSame('Original', $merged->getOriginal());
        $this->assertSame('Plural', $merged->plural);
        $this->assertSame(['Plural 1', 'Plural 2'], $merged->getPluralTranslations());

        $this->assertCount(3, $merged->getFlags());
        $this->assertSame(['flag-1', 'flag-2', 'flag-3'], $merged->getFlags()->toArray());

        $this->assertCount(3, $merged->getComments());
        $this->assertSame(['Comment 1', 'Comment 2', 'Comment 3'], $merged->getComments()->toArray());

        $this->assertCount(3, $merged->getReferences());
        $this->assertSame([
            'template.php' => [34, 44],
            'template2.php' => [55],
        ], $merged->getReferences()->toArray());

        $this->assertCount(1, $merged->getExtractedComments());
        $this->assertSame(['Extracted 1'], $merged->getExtractedComments()->toArray());

        $this->assertNotSame($merged, $translation1);
        $this->assertNotSame($merged, $translation2);
    }

    public function testMergeWithTheirStrategiesReplaceMetadata(): void
    {
        $ours = Translation::create(null, 'Hello');
        $ours->translation = 'Ciao';
        $ours->getReferences()->add('ours.php', 1);
        $ours->getExtractedComments()->add('ours note');

        $theirs = Translation::create(null, 'Hello');
        $theirs->getReferences()->add('theirs.php', 9);
        $theirs->getExtractedComments()->add('theirs note');

        $merged = $ours->mergeWith($theirs, Merge::REFERENCES_THEIRS | Merge::EXTRACTED_COMMENTS_THEIRS);

        $this->assertSame(['theirs.php' => [9]], $merged->getReferences()->toArray());
        $this->assertSame(['theirs note'], $merged->getExtractedComments()->toArray());
    }

    public function testMergeWithOverrideLetsTheirValuesWin(): void
    {
        $ours = Translation::create('old-ctx', 'Hello', 'Hello-plural');
        $ours->translation = 'ciao-ours';
        $ours->translatePlural('plurali-ours');
        $ours->previousContext = 'prev-ours';
        $ours->previousOriginal = 'orig-prev-ours';
        $ours->previousPlural = 'plur-prev-ours';

        $theirs = Translation::create('new-ctx', 'Hello', 'Hello-plural');
        $theirs->translation = 'ciao-theirs';
        $theirs->translatePlural('plurali-theirs');
        $theirs->previousContext = 'prev-theirs';
        $theirs->previousOriginal = 'orig-prev-theirs';
        $theirs->previousPlural = 'plur-prev-theirs';

        $merged = $ours->mergeWith($theirs, Merge::TRANSLATIONS_OVERRIDE);

        $this->assertSame('ciao-theirs', $merged->translation);
        $this->assertSame(['plurali-theirs'], $merged->getPluralTranslations());
        $this->assertSame('prev-theirs', $merged->previousContext);
        $this->assertSame('orig-prev-theirs', $merged->previousOriginal);
        $this->assertSame('plur-prev-theirs', $merged->previousPlural);
    }

    public function testPluralTranslationsArePaddedToTheRequestedSize(): void
    {
        $translation = Translation::create(null, 'One apple', '%d apples');
        $translation->translatePlural('%d mele');

        $this->assertSame(['%d mele'], $translation->getPluralTranslations());
        $this->assertSame(['%d mele', '', ''], $translation->getPluralTranslations(3));
        $this->assertSame([], $translation->getPluralTranslations(0));
    }
}
