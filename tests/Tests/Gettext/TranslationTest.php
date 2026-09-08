<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Merge;
use Omega\Gettext\References;
use Omega\Gettext\Translation;

covers(Comments::class);
covers(Flags::class);
covers(References::class);
covers(Translation::class);

it('creates and updates translations', function (): void {
    $translation = Translation::create('foo', 'bar');

    expect($translation->getContext())->toBe('foo');
    expect($translation->getOriginal())->toBe('bar');
    expect($translation->getId())->toBe("foo\004bar");
    expect($translation->isTranslated())->toBeFalse();

    $translation->translation = 'This is the translation';
    expect($translation->translation)->toBe('This is the translation');
    expect($translation->isTranslated())->toBeTrue();

    $translation->plural = 'bars';
    expect($translation->plural)->toBe('bars');

    $translation->translatePlural('bars-1', 'bars-2');
    expect($translation->getPluralTranslations())->toBe(['bars-1', 'bars-2']);

    expect($translation->disabled)->toBeFalse();

    $translation->disabled = true;
    expect($translation->disabled)->toBeTrue();

    $translation->disabled = false;
    expect($translation->disabled)->toBeFalse();

    $this->assertInstanceOf(Comments::class, $translation->getComments());
    $this->assertInstanceOf(Comments::class, $translation->getExtractedComments());
    $this->assertInstanceOf(Flags::class, $translation->getFlags());
    $this->assertInstanceOf(References::class, $translation->getReferences());

    $clone = clone $translation;

    $this->assertInstanceOf(Comments::class, $clone->getComments());
    $this->assertInstanceOf(Comments::class, $clone->getExtractedComments());
    $this->assertInstanceOf(Flags::class, $clone->getFlags());
    $this->assertInstanceOf(References::class, $clone->getReferences());

    expect($translation->getComments())->not->toBe($clone->getComments());
    expect($translation->getExtractedComments())->not->toBe($clone->getExtractedComments());
    expect($translation->getFlags())->not->toBe($clone->getFlags());
    expect($translation->getReferences())->not->toBe($clone->getReferences());
});

it('creates translations with plurals', function (): void {
    $translation = Translation::create('comments', 'One comment', '%s comments');

    expect($translation->getContext())->toBe('comments');
    expect($translation->getOriginal())->toBe('One comment');
    expect($translation->plural)->toBe('%s comments');
    expect($translation->getId())->toBe("comments\004One comment");

    $translation = Translation::create(null, 'Original');

    expect($translation->plural)->toBeNull();
});

it('merges translations', function (): void {
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

    expect($merged->getContext())->toBe('context');
    expect($merged->getOriginal())->toBe('Original');
    expect($merged->plural)->toBe('Plural');
    expect($merged->getPluralTranslations())->toBe(['Plural 1', 'Plural 2']);

    expect($merged->getFlags())->toHaveCount(3);
    expect($merged->getFlags()->toArray())->toBe(['flag-1', 'flag-2', 'flag-3']);

    expect($merged->getComments())->toHaveCount(3);
    expect($merged->getComments()->toArray())->toBe(['Comment 1', 'Comment 2', 'Comment 3']);

    expect($merged->getReferences())->toHaveCount(3);
    expect($merged->getReferences()->toArray())->toBe([
        'template.php' => [34, 44],
        'template2.php' => [55],
    ]);

    expect($merged->getExtractedComments())->toHaveCount(1);
    expect($merged->getExtractedComments()->toArray())->toBe(['Extracted 1']);

    expect($merged)->not->toBe($translation1);
    expect($merged)->not->toBe($translation2);
});

it('lets their strategies replace metadata', function (): void {
    $ours = Translation::create(null, 'Hello');
    $ours->translation = 'Ciao';
    $ours->getReferences()->add('ours.php', 1);
    $ours->getExtractedComments()->add('ours note');

    $theirs = Translation::create(null, 'Hello');
    $theirs->getReferences()->add('theirs.php', 9);
    $theirs->getExtractedComments()->add('theirs note');

    $merged = $ours->mergeWith($theirs, Merge::REFERENCES_THEIRS | Merge::EXTRACTED_COMMENTS_THEIRS);

    expect($merged->getReferences()->toArray())->toBe(['theirs.php' => [9]]);
    expect($merged->getExtractedComments()->toArray())->toBe(['theirs note']);
});

it('lets override merges let their values win', function (): void {
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

    expect($merged->translation)->toBe('ciao-theirs');
    expect($merged->getPluralTranslations())->toBe(['plurali-theirs']);
    expect($merged->previousContext)->toBe('prev-theirs');
    expect($merged->previousOriginal)->toBe('orig-prev-theirs');
    expect($merged->previousPlural)->toBe('plur-prev-theirs');
});

it('pads plural translations to the requested size', function (): void {
    $translation = Translation::create(null, 'One apple', '%d apples');
    $translation->translatePlural('%d mele');

    expect($translation->getPluralTranslations())->toBe(['%d mele']);
    expect($translation->getPluralTranslations(3))->toBe(['%d mele', '', '']);
    expect($translation->getPluralTranslations(0))->toBe([]);
});
