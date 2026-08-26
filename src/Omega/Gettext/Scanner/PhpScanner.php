<?php

declare(strict_types=1);

namespace Omega\Gettext\Scanner;

use Omega\Gettext\Translation;

use function array_keys;
use function preg_match;
use function strpos;

/**
 * Class to scan PHP files and get gettext translations
 */
class PhpScanner extends CodeScanner
{
    use FunctionsHandlersTrait;

    /** @var array<string, string> */
    protected array $functions = [
        'gettext' => 'gettext',
        '_' => 'gettext',
        '__' => 'gettext',
        'ngettext' => 'ngettext',
        'n__' => 'ngettext',
        'pgettext' => 'pgettext',
        'p__' => 'pgettext',
        'dgettext' => 'dgettext',
        'd__' => 'dgettext',
        'dngettext' => 'dngettext',
        'dn__' => 'dngettext',
        'dpgettext' => 'dpgettext',
        'dp__' => 'dpgettext',
        'npgettext' => 'npgettext',
        'np__' => 'npgettext',
        'dnpgettext' => 'dnpgettext',
        'dnp__' => 'dnpgettext',
        'noop' => 'gettext',
        'noop__' => 'gettext',
    ];

    public function getFunctionsScanner(): FunctionsScannerInterface
    {
        return new PhpFunctionsScanner(array_keys($this->functions));
    }

    /**
     * Registers a scanned entry, adding the php-format flag when needed.
     *
     * @param string|null $domain Domain of the entry; null uses the default domain.
     * @param string|null $context Context of the entry.
     * @param string $original Source string found by the scanner.
     * @param string|null $plural Optional source plural string.
     * @return Translation|null The stored or merged entry, or null when the domain is not registered.
     */
    protected function saveTranslation(
        ?string $domain,
        ?string $context,
        string $original,
        ?string $plural = null
    ): ?Translation {
        $translation = parent::saveTranslation($domain, $context, $original, $plural);

        if (!$translation) {
            return null;
        }

        $original = $translation->getOriginal();

        //Check if it includes a sprintf
        if (strpos($original, '%') !== false) {
            // %[argnum$][flags][width][.precision]specifier
            if (preg_match('/%(\d+\$)?([\-\+\s0]|\'.)?(\d+)?(\.\d+)?[bcdeEfFgGhHosuxX]/', $original)) {
                $translation->getFlags()->add('php-format');
            }
        }

        return $translation;
    }
}
