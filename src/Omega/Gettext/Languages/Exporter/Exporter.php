<?php

namespace Omega\Gettext\Languages\Exporter;

use Exception;

/**
 * Base class for all the exporters.
 */
abstract class Exporter
{
    /**
     * @var array
     */
    private static array $exporters;

    /**
     * Return the list of all the available exporters. Keys are the exporter handles, values are the exporter class names.
     *
     * @param bool $onlyForPublicUse if true, internal exporters will be omitted
     *
     * @return string[]
     */
    final public static function getExporters(bool $onlyForPublicUse = false): array
    {
        if (!isset(self::$exporters)) {
            $exporters = array();
            $m = null;
            foreach (scandir(__DIR__) as $f) {
                if (preg_match('/^(\w+)\.php$/', $f, $m)) {
                    if ($f !== basename(__FILE__)) {
                        $exporters[strtolower($m[1])] = $m[1];
                    }
                }
            }
            self::$exporters = $exporters;
        }
        if ($onlyForPublicUse) {
            $result = array();
            foreach (self::$exporters as $handle => $class) {
                if (call_user_func(self::getExporterClassName($handle) . '::isForPublicUse') === true) {
                    $result[$handle] = $class;
                }
            }
        } else {
            $result = self::$exporters;
        }

        return $result;
    }

    /**
     * Return the description of a specific exporter.
     *
     * @param string $exporterHandle the handle of the exporter
     *
     * @throws \Exception throws an Exception if $exporterHandle is not valid
     *
     * @return string
     */
    final public static function getExporterDescription(string $exporterHandle): string
    {
        $exporters = self::getExporters();
        if (!isset($exporters[$exporterHandle])) {
            throw new Exception("Invalid exporter handle: '{$exporterHandle}'");
        }

        return call_user_func(self::getExporterClassName($exporterHandle) . '::getDescription');
    }

    /**
     * Returns the fully qualified class name of a exporter given its handle.
     *
     * @param string $exporterHandle the exporter class handle
     *
     * @return string
     */
    final public static function getExporterClassName(string $exporterHandle): string
    {
        return __NAMESPACE__ . '\\' . ucfirst(strtolower($exporterHandle));
    }

    /**
     * Convert a list of Language instances to string.
     *
     * @param \Omega\Gettext\Languages\Language[] $languages the Language instances to convert
     * @param array|null $options
     *
     * @return string
     */
    final public static function toString(array $languages, ?array $options = null): string
    {
        if (!isset($options) || !is_array($options)) {
            $options = array();
        }
        if (isset($options['us-ascii']) && $options['us-ascii']) {
            $asciiList = array();
            foreach ($languages as $language) {
                $asciiList[] = $language->getUSAsciiClone();
            }
            $languages = $asciiList;
        }

        return static::toStringDoWithOptions($languages, $options);
    }

    /**
     * Save the Language instances to a file.
     *
     * @param \Omega\Gettext\Languages\Language[] $languages the Language instances to convert
     * @param array|null $options
     *
     * @throws \Exception
     */
    final public static function toFile(array $languages, string $filename, ?array $options = null): void
    {
        $data = self::toString($languages, $options);
        if (@file_put_contents($filename, $data) === false) {
            throw new Exception("Error writing data to '{$filename}'");
        }
    }

    /**
     * Is this exporter for public use?
     *
     * @return bool
     */
    public static function isForPublicUse(): bool
    {
        return true;
    }

    /**
     * Does this exporter supports exporting formulas both with and without extra parenthesis?
     *
     * @return bool
     */
    public static function supportsFormulasWithAndWithoutParenthesis(): bool
    {
        return false;
    }

    /**
     * Return a short description of the exporter.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        throw new Exception(get_called_class() . ' does not implement the method ' . __FUNCTION__);
    }

    /**
     * Convert a list of Language instances to string.
     *
     * @param \Omega\Gettext\Languages\Language[] $languages the Language instances to convert
     * @param array $options export options
     *
     * @return string
     */
    protected static function toStringDoWithOptions(array $languages, array $options): string
    {
        if (method_exists(get_called_class(), 'toStringDo')) {
            return static::toStringDo($languages);
        }
        throw new Exception(get_called_class() . ' does not implement the method ' . __FUNCTION__);
    }
}
