<?php

declare(strict_types=1);

namespace Gettext;

use LogicException;

abstract class TranslatorFunctions
{
    private static ?TranslatorInterface $translator = null;
    private static ?FormatterInterface $formatter = null;

    public static function register(TranslatorInterface $translator, ?FormatterInterface $formatter = null): void
    {
        self::$translator = $translator;
        self::$formatter = $formatter ?: new Formatter();

        include_once __DIR__ . '/functions.php';
    }

    public static function getTranslator(): TranslatorInterface
    {
        if (self::$translator === null) {
            throw new LogicException('No translator registered, call TranslatorFunctions::register() first');
        }

        return self::$translator;
    }

    public static function getFormatter(): FormatterInterface
    {
        if (self::$formatter === null) {
            throw new LogicException('No formatter registered, call TranslatorFunctions::register() first');
        }

        return self::$formatter;
    }
}
