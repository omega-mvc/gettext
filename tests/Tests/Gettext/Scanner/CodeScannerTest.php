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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CodeScanner::class)]
#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(ParsedFunction::class)]
#[CoversClass(PhpFunctionsScanner::class)]
#[CoversClass(PhpNodeVisitor::class)]
#[CoversClass(PhpScanner::class)]
#[CoversClass(References::class)]
#[CoversClass(Scanner::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
class CodeScannerTest extends TestCase
{
    public function testSetAndGetFunctionsMap(): void
    {
        $scanner = $this->createExposedScanner();

        $returned = $scanner->setFunctions(['__' => 'gettext', 'bad__' => 'noSuchMethod']);

        $this->assertSame($scanner, $returned);
        $this->assertSame(
            ['__' => 'gettext', 'bad__' => 'noSuchMethod'],
            $scanner->getFunctions()
        );
    }

    public function testUnknownFunctionNameYieldsNoHandler(): void
    {
        $scanner = $this->createExposedScanner();

        $parsed = new ParsedFunction('notRegistered', 'f.php', 3);

        $this->assertNull($scanner->exposedGetHandler($parsed));

        $scanner->exposedHandleFunction($parsed);

        $translations = $scanner->getTranslations()['messages'];

        $this->assertCount(0, $translations);
    }

    public function testHandlerThatIsNotCallableIsRejected(): void
    {
        $scanner = $this->createExposedScanner();
        $scanner->setFunctions(['__' => 'gettext', 'bad__' => 'noSuchMethod']);

        $parsed = new ParsedFunction('bad__', 'f.php', 1);
        $parsed->addArgument('text');

        $this->assertNull($scanner->exposedGetHandler($parsed));
    }

    public function testKnownHandlerReceivesTheParsedCall(): void
    {
        $scanner = $this->createExposedScanner();

        $parsed = new ParsedFunction('__', 'f.php', 5);
        $parsed->addArgument('Hello');

        $this->assertNotNull($scanner->exposedGetHandler($parsed));

        $scanner->exposedHandleFunction($parsed);

        $translation = $scanner->getTranslations()['messages']->find(null, 'Hello');

        $this->assertNotNull($translation);
        $this->assertSame(['f.php' => [5]], $translation->getReferences()->toArray());
    }

    public function testAddReferencesCanBeDisabled(): void
    {
        $scanner = $this->createExposedScanner();
        $scanner->addReferences(false);

        $parsed = new ParsedFunction('__', 'f.php', 5);
        $parsed->addArgument('Hello');

        $scanner->exposedHandleFunction($parsed);

        $translation = $scanner->getTranslations()['messages']->find(null, 'Hello');

        $this->assertNotNull($translation);
        $this->assertSame([], $translation->getReferences()->toArray());
    }

    public function testFlagsAndPrefixedCommentsFlowThroughHandlers(): void
    {
        $scanner = $this->createExposedScanner();
        $scanner->extractCommentsStartingWith('translators:');

        $parsed = new ParsedFunction('__', 'f.php', 7);
        $parsed->addArgument('Hi');
        $parsed->addFlag('php-format');
        $parsed->addComment('random note');
        $parsed->addComment('translators: real');

        $scanner->exposedHandleFunction($parsed);

        $translation = $scanner->getTranslations()['messages']->find(null, 'Hi');

        $this->assertNotNull($translation);
        $this->assertSame(['php-format'], $translation->getFlags()->toArray());
        $this->assertSame(['translators: real'], $translation->getExtractedComments()->toArray());
    }

    public function testMissingArgumentsThrowsWithoutTolerance(): void
    {
        $scanner = $this->createExposedScanner();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('At least 2 arguments are required');

        $scanner->scanString("<?php ngettext('only');", 'f.php');
    }

    public function testScanFileThrowsWhenFileIsUnreadable(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'gettext-unreadable');
        chmod((string) $file, 0000);

        try {
            $scanner = $this->createExposedScanner();

            $this->expectException(Exception::class);
            $this->expectExceptionMessage("Cannot read the file '$file', probably permissions");

            $scanner->scanFile((string) $file);
        } finally {
            chmod((string) $file, 0600);
            unlink((string) $file);
        }
    }

    private function createExposedScanner(): ExposedCodeScanner
    {
        $scanner = new ExposedCodeScanner(Translations::create('messages'));
        $scanner->setDefaultDomain('messages');

        return $scanner;
    }
}
