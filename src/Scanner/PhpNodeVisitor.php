<?php

declare(strict_types=1);

namespace Gettext\Scanner;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Print_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitor;

class PhpNodeVisitor implements NodeVisitor
{
    /** @var array<string>|null */
    protected ?array $validFunctions;
    protected string $filename;

    /** @var list<ParsedFunction> */
    protected array $functions = [];

    /** @var list<Node> */
    protected array $bufferComments = [];

    /**
     * @param array<string>|null $validFunctions
     */
    public function __construct(string $filename, ?array $validFunctions = null)
    {
        $this->filename = $filename;
        $this->validFunctions = $validFunctions;
    }

    public function beforeTraverse(array $nodes)
    {
        return null;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof FuncCall || $node instanceof MethodCall || $node instanceof StaticCall) {
            $name = static::getName($node);

            if ($name && ($this->validFunctions === null || in_array($name, $this->validFunctions))) {
                $this->functions[] = $this->createFunction($node);
            } elseif ($node->getComments()) {
                $this->bufferComments[] = $node;
            }

            return null;
        }

        if (
            $node instanceof Expression
            || $node instanceof Echo_
            || $node instanceof Return_
            || $node instanceof Print_
            || $node instanceof Assign
        ) {
            $this->bufferComments[] = $node;
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        return null;
    }

    public function afterTraverse(array $nodes)
    {
        return null;
    }

    /**
     * @return list<ParsedFunction>
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * @param FuncCall|MethodCall|StaticCall $node
     */
    protected function createFunction(Expr $node): ParsedFunction
    {
        $function = new ParsedFunction(
            static::getName($node) ?? '',
            $this->filename,
            $node->getStartLine(),
            $node->getEndLine()
        );

        foreach ($node->getComments() as $comment) {
            $function->addComment(static::getComment($comment));
        }

        if ($this->bufferComments) {
            foreach ($this->bufferComments as $bufferComment) {
                if ($bufferComment->getStartLine() === $node->getStartLine()) {
                    foreach ($bufferComment->getComments() as $comment) {
                        $function->addComment(static::getComment($comment));
                    }
                }
            }
        }

        $this->bufferComments = [];

        foreach ($node->args as $argument) {
            foreach ($argument->getComments() as $comment) {
                $function->addComment(static::getComment($comment));
            }

            if ($argument instanceof Arg) {
                $function->addArgument(static::getValue($argument->value));
            } else {
                $function->addArgument();
            }
        }

        return $function;
    }

    protected static function getComment(Comment $comment): string
    {
        $text = $comment->getReformattedText();

        $lines = array_map(function ($line) {
            $line = ltrim($line, "#*/ \t");
            $line = rtrim($line, "#*/ \t");
            return trim($line);
        }, explode("\n", $text));

        return trim(implode("\n", $lines));
    }

    protected static function getName(Node $node): ?string
    {
        if (!$node instanceof FuncCall && !$node instanceof MethodCall && !$node instanceof StaticCall) {
            return null;
        }

        $name = $node->name;

        if ($name instanceof Name) {
            return $name->getLast();
        }

        if ($name instanceof Identifier) {
            return (string) $name;
        }

        return null;
    }

    protected static function getValue(Expr $value): mixed
    {
        if ($value instanceof String_ || $value instanceof Int_ || $value instanceof Float_) {
            return $value->value;
        }

        if ($value instanceof Concat) {
            $values = [];

            foreach ($value->getSubNodeNames() as $subName) {
                $subValue = $value->$subName;

                if (!$subValue instanceof Expr) {
                    continue;
                }

                $part = static::getValue($subValue);

                if (is_scalar($part)) {
                    $values[] = (string) $part;
                }
            }

            return implode('', $values);
        }

        if ($value instanceof Array_) {
            $arr = [];

            foreach ($value->items as $item) {
                $itemValue = static::getValue($item->value);

                if ($item->key === null) {
                    $arr[] = $itemValue;
                    continue;
                }

                $key = static::getValue($item->key);

                if (is_int($key) || is_string($key)) {
                    $arr[$key] = $itemValue;
                } else {
                    $arr[] = $itemValue;
                }
            }

            return $arr;
        }

        return null;
    }
}
