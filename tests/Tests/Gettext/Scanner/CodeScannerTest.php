<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Scanner;

use Exception;
use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\References;
use Omega\Gettext\Scanner\CodeScanner;
use Omega\Gettext\Scanner\ParsedFunction;
use Omega\Gettext\Scanner\PhpFunctionsScanner;
use Omega\Gettext\Scanner\PhpNodeVisitor;
use Omega\Gettext\Scanner\PhpScanner;
use Omega\Gettext\Scanner\Scanner;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;

covers(CodeScanner::class);
covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(ParsedFunction::class);
covers(PhpFunctionsScanner::class);
covers(PhpNodeVisitor::class);
covers(PhpScanner::class);
covers(References::class);
covers(Scanner::class);
covers(Translation::class);
covers(Translations::class);

it('sets and gets the functions map', function (): void {
    $scanner = createExposedScanner();

    $returned = $scanner->setFunctions(['__' => 'gettext', 'bad__' => 'noSuchMethod']);

    expect($returned)->toBe($scanner);
    expect($scanner->getFunctions())->toBe(['__' => 'gettext', 'bad__' => 'noSuchMethod']);
});

it('yields no handler for unknown function names', function (): void {
    $scanner = createExposedScanner();

    $parsed = new ParsedFunction('notRegistered', 'f.php', 3);

    expect($scanner->exposedGetHandler($parsed))->toBeNull();

    $scanner->exposedHandleFunction($parsed);

    $translations = $scanner->getTranslations()['messages'];

    expect($translations)->toHaveCount(0);
});

it('rejects handlers that are not callable', function (): void {
    $scanner = createExposedScanner();
    $scanner->setFunctions(['__' => 'gettext', 'bad__' => 'noSuchMethod']);

    $parsed = new ParsedFunction('bad__', 'f.php', 1);
    $parsed->addArgument('text');

    expect($scanner->exposedGetHandler($parsed))->toBeNull();
});

it('delivers the parsed call to a known handler', function (): void {
    $scanner = createExposedScanner();

    $parsed = new ParsedFunction('__', 'f.php', 5);
    $parsed->addArgument('Hello');

    expect($scanner->exposedGetHandler($parsed))->not->toBeNull();

    $scanner->exposedHandleFunction($parsed);

    $translation = $scanner->getTranslations()['messages']->find(null, 'Hello');

    $this->assertNotNull($translation);
    expect($translation->getReferences()->toArray())->toBe(['f.php' => [5]]);
});

it('can disable the references', function (): void {
    $scanner = createExposedScanner();
    $scanner->addReferences(false);

    $parsed = new ParsedFunction('__', 'f.php', 5);
    $parsed->addArgument('Hello');

    $scanner->exposedHandleFunction($parsed);

    $translation = $scanner->getTranslations()['messages']->find(null, 'Hello');

    $this->assertNotNull($translation);
    expect($translation->getReferences()->toArray())->toBe([]);
});

it('flows flags and prefixed comments through the handlers', function (): void {
    $scanner = createExposedScanner();
    $scanner->extractCommentsStartingWith('translators:');

    $parsed = new ParsedFunction('__', 'f.php', 7);
    $parsed->addArgument('Hi');
    $parsed->addFlag('php-format');
    $parsed->addComment('random note');
    $parsed->addComment('translators: real');

    $scanner->exposedHandleFunction($parsed);

    $translation = $scanner->getTranslations()['messages']->find(null, 'Hi');

    $this->assertNotNull($translation);
    expect($translation->getFlags()->toArray())->toBe(['php-format']);
    expect($translation->getExtractedComments()->toArray())->toBe(['translators: real']);
});

it('throws without tolerance on missing arguments', function (): void {
    $scanner = createExposedScanner();

    expect(fn () => $scanner->scanString("<?php ngettext('only');", 'f.php'))
        ->toThrow(Exception::class, 'At least 2 arguments are required');
});

it('throws when the scanned file is unreadable', function (): void {
    $file = tempnam(sys_get_temp_dir(), 'gettext-unreadable');
    chmod((string) $file, 0000);

    try {
        $scanner = createExposedScanner();

        expect(fn () => $scanner->scanFile((string) $file))
            ->toThrow(Exception::class, "Cannot read the file '$file', probably permissions");
    } finally {
        chmod((string) $file, 0600);
        unlink((string) $file);
    }
});

function createExposedScanner(): ExposedCodeScanner
{
    $scanner = new ExposedCodeScanner(Translations::create('messages'));
    $scanner->setDefaultDomain('messages');

    return $scanner;
}
