<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Scanner;

use Exception;
use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\References;
use Omega\Gettext\Scanner\ParsedFunction;
use Omega\Gettext\Scanner\PhpFunctionsScanner;
use Omega\Gettext\Scanner\PhpNodeVisitor;
use Omega\Gettext\Scanner\PhpScanner;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;

covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(ParsedFunction::class);
covers(PhpFunctionsScanner::class);
covers(PhpNodeVisitor::class);
covers(PhpScanner::class);
covers(References::class);
covers(Translation::class);
covers(Translations::class);

it('scans php code', function (): void {
    $file = __DIR__ . '/../assets/code.php';

    $scanner = new PhpScanner(
        Translations::create('domain1'),
        Translations::create('domain2'),
        Translations::create('domain3')
    );

    expect($scanner->getTranslations())->toHaveCount(3);

    $scanner->scanFile($file);

    /**
     * @var Translations $domain1
     * @var Translations $domain2
     * @var Translations $domain3
     */
    ['domain1' => $domain1, 'domain2' => $domain2, 'domain3' => $domain3] = $scanner->getTranslations();

    expect($domain1)->toHaveCount(6);
    expect($domain2)->toHaveCount(4);
    expect($domain3)->toHaveCount(1);

    $scanner->setDefaultDomain('domain1');
    $scanner->extractCommentsStartingWith('');
    $scanner->scanFile($file);

    expect($domain1)->toHaveCount(39);
    expect($domain2)->toHaveCount(4);
    expect($domain3)->toHaveCount(1);

    //Extract comments
    $translation = $domain1->find('CONTEXT', 'All comments');
    $this->assertNotNull($translation);
    expect($translation->getReferences()->toArray())->toBe([$file => [66]]);
    expect($translation->getExtractedComments())->toHaveCount(1);

    $translation = $domain1->find(null, 'i18n tagged %s');
    $this->assertNotNull($translation);
    expect($translation->getReferences()->toArray())->toBe([$file => [75]]);
    expect($translation->getExtractedComments())->toHaveCount(1);
    expect($translation->getExtractedComments()->toArray())->toBe(['i18n Tagged comment on the line before']);
    expect($translation->getFlags()->toArray())->toBe(['php-format']);
});

it('throws on invalid functions', function (): void {
    expect(function (): void {
        $scanner = new PhpScanner(Translations::create('messages'));
        $scanner->scanString('<?php __(ucfirst("invalid function"));', 'file.php');
    })->toThrow(Exception::class);
});

it('ignores invalid functions when tolerant', function (): void {
    $scanner = new PhpScanner(Translations::create('messages'));
    $scanner->ignoreInvalidFunctions();
    $scanner->scanString('<?php __(ucfirst("invalid function"));', 'file.php');

    ['messages' => $translations] = $scanner->getTranslations();

    expect($translations)->toHaveCount(0);
});
