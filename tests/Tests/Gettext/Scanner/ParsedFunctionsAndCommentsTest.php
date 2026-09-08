<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Scanner;

use Omega\Gettext\Scanner\ParsedFunction;

covers(ParsedFunction::class);

it('collects arguments, comments and flags', function (): void {
    $function = new ParsedFunction('__', 'template.php', 45);

    expect($function->getName())->toBe('__');
    expect($function->getFilename())->toBe('template.php');
    expect($function->getLine())->toBe(45);
    expect($function->getLastLine())->toBe(45);

    $function->addArgument('a');
    expect($function->getArguments())->toBe(['a']);

    $function->addArgument('c');
    expect($function->getArguments())->toBe(['a', 'c']);

    $function->addComment('This is a comment');
    expect($function->getComments())->toBe(['This is a comment']);

    $function->addComment('This is other comment');
    expect($function->getComments())->toBe(['This is a comment', 'This is other comment']);

    $function->addFlag('php-format');
    expect($function->getFlags())->toBe(['php-format']);
});
