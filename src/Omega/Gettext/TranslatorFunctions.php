<?php

declare(strict_types=1);

namespace Omega\Gettext;

use LogicException;

/**
 * Static registry binding the translator and formatter used by the
 * application at runtime.
 *
 * After register(), getTranslator() exposes the active translator from
 * anywhere (service locators, view helpers, facade patterns).
 *
 * The class is abstract on purpose: it only exposes static state.
 */
abstract class TranslatorFunctions
{
    /** @var TranslatorInterface|null Currently registered translator. */
    private static ?TranslatorInterface $translator = null;

    /** @var FormatterInterface|null Formatter paired with the translator. */
    private static ?FormatterInterface $formatter = null;

    /**
     * Stores the translator and its formatter as the global instances.
     *
     * @param TranslatorInterface $translator Translator to activate.
     * @param FormatterInterface|null $formatter Optional renderer; defaults to a new Formatter.
     */
    public static function register(TranslatorInterface $translator, ?FormatterInterface $formatter = null): void
    {
        self::$translator = $translator;
        self::$formatter = $formatter ?: new Formatter();
    }

    /**
     * Returns the registered translator.
     *
     * @return TranslatorInterface The instance passed to register().
     *
     * @throws LogicException If register() has not been called yet.
     */
    public static function getTranslator(): TranslatorInterface
    {
        if (self::$translator === null) {
            throw new LogicException('No translator registered, call TranslatorFunctions::register() first');
        }

        return self::$translator;
    }

    /**
     * Returns the registered formatter.
     *
     * @return FormatterInterface The instance passed to register(), or the default one.
     *
     * @throws LogicException If register() has not been called yet.
     */
    public static function getFormatter(): FormatterInterface
    {
        if (self::$formatter === null) {
            throw new LogicException('No formatter registered, call TranslatorFunctions::register() first');
        }

        return self::$formatter;
    }
}
