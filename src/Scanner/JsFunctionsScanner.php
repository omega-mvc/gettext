<?php

declare(strict_types=1);

namespace Gettext\Scanner;

use Peast\Peast;
use Peast\Syntax\Parser;
use Peast\Traverser;

class JsFunctionsScanner implements FunctionsScannerInterface
{
    /** @var array<string>|null */
    protected ?array $validFunctions;

    /** @var array{string, array<string, bool>} */
    protected array $parser;

    /**
     * @param array<string>|null $validFunctions
     */
    public function __construct(?array $validFunctions = null)
    {
        $this->validFunctions = $validFunctions;
        $this->parser('latest');
    }

    /**
     * @param array<string, bool> $options
     */
    public function parser(string $version, array $options = ['comments' => true]): self
    {
        $this->parser = [$version, $options];

        return $this;
    }

    public function scan(string $code, string $filename): array
    {
        [$version, $options] = $this->parser;

        $parser = Peast::$version($code, $options);

        if (!$parser instanceof Parser) {
            return [];
        }

        $ast = $parser->parse();

        $traverser = new Traverser();
        $visitor = new JsNodeVisitor($filename, $this->validFunctions);
        $traverser->addFunction($visitor);
        $traverser->traverse($ast);

        return $visitor->getFunctions();
    }
}
