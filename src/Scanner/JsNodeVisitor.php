<?php

declare(strict_types=1);

namespace Gettext\Scanner;

use Peast\Syntax\Node\CallExpression;
use Peast\Syntax\Node\Comment;
use Peast\Syntax\Node\Identifier;
use Peast\Syntax\Node\Literal;
use Peast\Syntax\Node\MemberExpression;
use Peast\Syntax\Node\Node;
use Peast\Syntax\Node\SpreadElement;
use Peast\Syntax\Node\TemplateLiteral;

class JsNodeVisitor
{
    /** @var array<string>|null */
    protected ?array $validFunctions;

    protected string $filename;

    /** @var list<ParsedFunction> */
    protected $functions = [];

    /**
     * @param array<string>|null $validFunctions
     */
    public function __construct(string $filename, ?array $validFunctions = null)
    {
        $this->filename = $filename;
        $this->validFunctions = $validFunctions;
    }

    public function __invoke(Node $node): void
    {
        if ($node instanceof CallExpression) {
            $function = $this->createFunction($node);

            if ($function !== null) {
                $this->functions[] = $function;
            }
        }
    }

    /**
     * @return list<ParsedFunction>
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    protected function createFunction(CallExpression $node): ?ParsedFunction
    {
        $name = static::getFunctionName($node);

        if (empty($name) || ($this->validFunctions !== null && !in_array($name, $this->validFunctions))) {
            return null;
        }

        $position = $node->getLocation();

        $function = new ParsedFunction(
            $name,
            $this->filename,
            $position->getStart()->getLine(),
            $position->getEnd()->getLine()
        );

        $callee = $node->getCallee();

        if ($callee instanceof Node) {
            static::addComments($function, $callee);
        }

        foreach ($node->getArguments() as $argument) {
            if ($argument instanceof SpreadElement) {
                $function->addArgument();
                continue;
            }

            if ($argument instanceof Literal) {
                $function->addArgument($argument->getValue());
                static::addComments($function, $argument);
                continue;
            }

            if ($argument instanceof TemplateLiteral) {
                if ($argument->getExpressions()) {
                    $function->addArgument();
                    continue;
                }

                $quasis = $argument->getQuasis();
                $quasi = array_shift($quasis);

                if ($quasi !== null) {
                    $function->addArgument($quasi->getValue());
                    static::addComments($function, $argument);
                    continue;
                }
            }

            $function->addArgument();
        }

        return $function;
    }

    protected static function getFunctionName(CallExpression $node): ?string
    {
        $callee = $node->getCallee();

        if ($callee instanceof Identifier) {
            return $callee->getName();
        }

        if ($callee instanceof MemberExpression) {
            $property = $callee->getProperty();

            return $property instanceof Identifier ? $property->getName() : null;
        }

        return null;
    }

    protected static function addComments(ParsedFunction $function, Node $node): void
    {
        foreach ($node->getLeadingComments() as $comment) {
            $function->addComment(static::getComment($comment));
        }
    }

    protected static function getComment(Comment $comment): string
    {
        $text = $comment->getText();

        $lines = array_map(function ($line) {
            $line = ltrim($line, "#*/ \t");
            $line = rtrim($line, "#*/ \t");
            return trim($line);
        }, explode("\n", $text));

        return trim(implode("\n", $lines));
    }
}
