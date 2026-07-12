<?php

declare(strict_types=1);

namespace Gettext\Generator;

use Gettext\Translations;

use function count;
use function explode;
use function implode;
use function is_array;
use function sprintf;
use function strtr;

/**
 * Class PoGenerator
 *
 * Generates gettext .po files from a collection of translations.
 *
 * This final class extends `AbstractGenerator` and implements `generateString()`
 * to produce human-readable .po files. It handles translation comments,
 * extracted comments, references, flags, plural forms, previous translations,
 * and optional headers.
 *
 * Multiline strings are properly escaped and formatted according to the
 * gettext .po file format.
 */
final class PoGenerator extends AbstractGenerator
{
    /**
     * Generates the .po file content from the provided translations.
     *
     * Each translation may include context, previous strings, plural forms,
     * comments, extracted comments, references, and flags. Headers and description
     * of the translations are included at the top.
     *
     * @param Translations $translations The collection of translations
     * @return string The resulting .po file content
     */
    public function generateString(Translations $translations): string
    {
        $pluralForm = $translations->getHeaders()->getPluralForm();
        $pluralSize = is_array($pluralForm) ? ($pluralForm[0] - 1) : null;
        $lines = [];

        //Description and flags
        if ($translations->getDescription()) {
            $description = explode("\n", $translations->getDescription());

            foreach ($description as $line) {
                $lines[] = sprintf('# %s', $line);
            }

            $lines[] = '#';
        }

        if (count($translations->getFlags())) {
            $lines[] = sprintf('#, %s', implode(',', $translations->getFlags()->toArray()));
        }

        //Headers
        $lines[] = 'msgid ""';
        $lines[] = 'msgstr ""';

        foreach ($translations->getHeaders() as $name => $value) {
            $lines[] = sprintf('"%s: %s\\n"', $name, $value);
        }

        $lines[] = '';

        //Translations
        foreach ($translations as $translation) {
            foreach ($translation->getComments() as $comment) {
                $lines[] = sprintf('# %s', $comment);
            }

            foreach ($translation->getExtractedComments() as $comment) {
                $lines[] = sprintf('#. %s', $comment);
            }

            foreach ($translation->getReferences() as $filename => $lineNumbers) {
                if (empty($lineNumbers)) {
                    $lines[] = sprintf('#: %s', $filename);
                    continue;
                }

                foreach ($lineNumbers as $number) {
                    $lines[] = sprintf('#: %s:%d', $filename, $number);
                }
            }

            if (count($translation->getFlags())) {
                $lines[] = sprintf('#, %s', implode(',', $translation->getFlags()->toArray()));
            }

            $prefix = $translation->isDisabled() ? '#~ ' : '';

            if ($context = $translation->getPreviousContext()) {
                $lines[] = sprintf('%s#| msgctxt %s', $prefix, self::encode($context));
            }

            if ($original = $translation->getPreviousOriginal()) {
                $lines[] = sprintf('%s#| msgid %s', $prefix, self::encode($original));
            }

            if ($plural = $translation->getPreviousPlural()) {
                $lines[] = sprintf('%s#| msgid_plural %s', $prefix, self::encode($plural));
            }

            if ($context = $translation->getContext()) {
                $lines[] = sprintf('%smsgctxt %s', $prefix, self::encode($context));
            }

            self::appendLines($lines, $prefix, 'msgid', $translation->getOriginal());

            if ($plural = $translation->getPlural()) {
                self::appendLines($lines, $prefix, 'msgid_plural', $plural);
                self::appendLines($lines, $prefix, 'msgstr[0]', $translation->getTranslation() ?: '');

                foreach ($translation->getPluralTranslations($pluralSize) as $k => $v) {
                    self::appendLines($lines, $prefix, sprintf('msgstr[%d]', $k + 1), $v);
                }
            } else {
                self::appendLines($lines, $prefix, 'msgstr', $translation->getTranslation() ?: '');
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Appends one or more lines for a translation entry, handling multiline strings.
     *
     * @param array  $lines  The array of lines to append to (passed by reference)
     * @param string $prefix A prefix for the line, e.g. "#~ " for disabled translations
     * @param string $name   The PO directive name, e.g. 'msgid', 'msgstr', 'msgid_plural'
     * @param string $value  The string value to encode and append
     * @return void
     */
    private static function appendLines(array &$lines, string $prefix, string $name, string $value): void
    {
        $newLines = explode("\n", $value);
        $total    = count($newLines);

        if ($total === 1) {
            $lines[] = sprintf('%s%s %s', $prefix, $name, self::encode($newLines[0]));

            return;
        }

        $lines[] = sprintf('%s%s ""', $prefix, $name);

        $last = $total - 1;
        foreach ($newLines as $k => $line) {
            if ($k < $last) {
                $line .= "\n";
            }

            $lines[] = self::encode($line);
        }
    }

    /**
     * Encodes a string to a valid PO representation.
     *
     * Escapes backslashes, quotes, tabs, carriage returns, and newlines.
     *
     * @param string $value The string to encode
     * @return string The encoded string ready for use in a .po file
     */
    public static function encode(string $value): string
    {
        return '"' . strtr(
            $value,
            [
                "\x00" => '',
                '\\'   => '\\\\',
                "\t"   => '\t',
                "\r"   => '\r',
                "\n"   => '\n',
                '"'    => '\\"',
            ]
        ).'"';
    }
}
