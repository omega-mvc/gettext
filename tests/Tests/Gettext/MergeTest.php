<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Brick\VarExporter\VarExporter;
use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\Merge;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;

covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(References::class);
covers(Merge::class);
covers(Translation::class);
covers(Translations::class);

it('merges without a strategy', function (): void {
    $pot = createPOT();
    $po = createPO();

    $merged = $pot->mergeWith($po);

    assertSnapshot('testNoStrategy', $merged);
});

it('uses the scan and load strategy', function (): void {
    $pot = createPOT();
    $po = createPO();

    $strategy = Merge::HEADERS_OVERRIDE         // Override the headers with the PO values
              | Merge::TRANSLATIONS_OURS        // Keep only the scanned entries
              | Merge::TRANSLATIONS_OVERRIDE    // Apply the changes of the PO
              | Merge::EXTRACTED_COMMENTS_OURS  // Keep only the extracted comments
              | Merge::REFERENCES_OURS          // Keep only the scanned references
              | Merge::FLAGS_THEIRS             // Keep the flags in PO
              | Merge::COMMENTS_THEIRS;         // Keep the comments in PO

    expect(Merge::SCAN_AND_LOAD)->toBe($strategy);

    $merged = $pot->mergeWith($po, $strategy);

    assertSnapshot('testScanAndLoadStrategy', $merged);
});

function createPOT(): Translations
{
    $translations = Translations::create('my-domain');
    $translations->getHeaders()
        ->set('POT-Creation-Date', '2019-10-10 10:10:10')
        ->set('Last-Translator', '')
        ->set('X-Foo', 'foo')
        ->set('X-Generator', 'PHP Gettext scanner');
    $translations->getFlags()->add('fuzzy');

    $translation = Translation::create(null, 'title');
    $translation->getReferences()->add('template.php', 3);
    $translations->add($translation);

    $translation = Translation::create(null, 'intro');
    $translation->getReferences()->add('template.php', 4);
    $translations->add($translation);

    $translation = Translation::create(null, 'one comment');
    $translation->plural = '%s comments';
    $translation->getReferences()->add('template.php', 5);
    $translation->getExtractedComments()->add('Number of comments of the article');
    $translations->add($translation);

    $translation = Translation::create(null, 'This is a flagged element');
    $translation->getReferences()->add('template.php', 10);
    $translation->getFlags()->add('c-code');
    $translations->add($translation);

    $translation = Translation::create(null, 'This is a new translation');
    $translation->getReferences()->add('template.php', 11);
    $translations->add($translation);

    return $translations;
}

function createPO(): Translations
{
    $translations = Translations::create('my-domain');
    $translations->getHeaders()
        ->set('Last-Translator', 'Oscar')
        ->set('X-Generator', 'PHP Gettext scanner')
        ->set('Language-Team', 'My Team')
        ->set('X-Foo', 'bar')
        ->set('Language', 'gl_ES');
    $translations->description = 'This is a description';

    $translation = Translation::create(null, 'title');
    $translation->getReferences()
        ->add('template.php', 2)
        ->add('other-template.php', 2);
    $translation->translation = 'Título';
    $translations->add($translation);

    $translation = Translation::create(null, 'subtitle');
    $translation->getReferences()->add('template.php', 2);
    $translation->translation = 'Subtítulo';
    $translations->add($translation);

    $translation = Translation::create(null, 'intro');
    $translation->getReferences()->add('template.php', 4);
    $translation->getComments()->add('Disabled comment');
    $translation->translation = 'Intro';
    $translation->disabled = true;
    $translations->add($translation);

    $translation = Translation::create(null, 'one comment');
    $translation->plural = '%s comments';
    $translation->getReferences()->add('template.php', 6);
    $translation->getExtractedComments()->add('Number of comments of the article');
    $translation->translation = 'Un comentario';
    $translation->translatePlural('%s comentarios');
    $translations->add($translation);

    $translation = Translation::create(null, 'This is a flagged element');
    $translation->getFlags()->add('a-code');
    $translation->getComments()->add('This is a comment');
    $translations->add($translation);

    return $translations;
}

function assertSnapshot(string $name, Translations $translations, bool $forceCreate = false): void
{
    $file = __DIR__ . "/snapshots/{$name}.php";
    $array = $translations->toArray();

    if (!is_file($file) || $forceCreate) {
        $code = sprintf('<?php %s', VarExporter::export($array, VarExporter::ADD_RETURN));
        file_put_contents($file, $code);
    }

    $expected = require $file;
    expect($array)->toBe($expected);
}
