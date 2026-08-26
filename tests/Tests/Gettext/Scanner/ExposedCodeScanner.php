<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Scanner;

use Omega\Gettext\Scanner\ParsedFunction;
use Omega\Gettext\Scanner\PhpScanner;

final class ExposedCodeScanner extends PhpScanner
{
    public function exposedHandleFunction(ParsedFunction $function): void
    {
        $this->handleFunction($function);
    }

    public function exposedGetHandler(ParsedFunction $function): ?callable
    {
        return $this->getFunctionHandler($function);
    }
}
