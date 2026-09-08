<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use InvalidArgumentException;
use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Generator\ArrayGenerator;
use Omega\Gettext\Headers;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;
use Omega\Gettext\Translator;

covers(ArrayGenerator::class);
covers(Comments::class);
covers(Flags::class);
covers(Headers::class);
covers(References::class);
covers(Translation::class);
covers(Translations::class);
covers(Translator::class);

const TRANSLATOR_TEST_PLURAL_2 = 'nplurals=2; plural=(n != 1);';

const TRANSLATOR_TEST_PLURAL_3 = 'nplurals=3; plural=(n==1 ? 0 : n%10>=2 && n%10<=4 ? 1 : 2);';

it('creates a translator from translations', function (): void {
    $translations = Translations::create('messages');
    $translations->getHeaders()->set(Headers::HEADER_PLURAL, TRANSLATOR_TEST_PLURAL_2);

    $hello = Translation::create(null, 'Hello');
    $hello->translation = 'Ciao';

    $apple = Translation::create(null, 'One apple', '%d apples');
    $apple->translation = 'Una mela';
    $apple->translatePlural('%d mele');

    $translations->add($hello);
    $translations->add($apple);

    $translator = Translator::createFromTranslations($translations);

    expect($translator->gettext('Hello'))->toBe('Ciao');
    expect($translator->ngettext('One apple', '%d apples', 1))->toBe('Una mela');
    expect($translator->ngettext('One apple', '%d apples', 10))->toBe('%d mele');
});

it('returns the translation or the original', function (): void {
    $translator = new Translator();
    $translator->addTranslations([
        'domain' => 'messages',
        'messages' => [
            '' => [
                'Hello' => 'Ciao',
            ],
        ],
    ]);

    expect($translator->gettext('Hello'))->toBe('Ciao');
    expect($translator->gettext('Missing'))->toBe('Missing');
});

it('falls back to the original for empty translations', function (): void {
    $translator = new Translator();
    $translator->addTranslations([
        'domain' => 'messages',
        'messages' => [
            '' => [
                'Void' => '',
            ],
        ],
    ]);

    expect($translator->gettext('Void'))->toBe('Void');
});

it('resolves context lookups', function (): void {
    $translator = new Translator();
    $translator->addTranslations([
        'domain' => 'messages',
        'messages' => [
            '' => [
                'Save' => 'Salvare',
            ],
            'toolbar' => [
                'Save' => 'Salva',
                'One item' => ['Una voce', '%d voci'],
            ],
        ],
    ]);

    expect($translator->gettext('Save'))->toBe('Salvare');
    expect($translator->pgettext('toolbar', 'Save'))->toBe('Salva');
    expect($translator->npgettext('toolbar', 'One item', '%d items', 1))
        ->toBe('Una voce');
    expect($translator->npgettext('toolbar', 'One item', '%d items', 3))
        ->toBe('%d voci');
    expect($translator->pgettext('missing-context', 'Ghost'))->toBe('Ghost');
});

it('resolves domain lookups', function (): void {
    $translator = new Translator();
    $translator->addTranslations([
        'domain' => 'errors',
        'plural-forms' => TRANSLATOR_TEST_PLURAL_2,
        'messages' => [
            '' => [
                'One error' => ['Un errore', '%d errori'],
            ],
        ],
    ]);

    expect($translator->dgettext('errors', 'One error'))->toBe('Un errore');
    expect($translator->dngettext('errors', 'One error', '%d errori', 5))->toBe('%d errori');
    expect($translator->dgettext('missing-domain', 'Unknown'))->toBe('Unknown');
    expect($translator->dpgettext('missing-domain', 'ctx', 'Unknown'))->toBe('Unknown');
});

it('resolves plurals with a two form formula', function (): void {
    $translator = new Translator();
    $translator->addTranslations([
        'domain' => 'messages',
        'plural-forms' => TRANSLATOR_TEST_PLURAL_2,
        'messages' => [
            '' => [
                'One file' => ['Un file', '%d files'],
            ],
        ],
    ]);

    expect($translator->ngettext('One file', '%d files', 1))->toBe('Un file');
    expect($translator->ngettext('One file', '%d files', 2))->toBe('%d files');
    expect($translator->ngettext('One file', '%d files', 0))->toBe('%d files');
});

it('resolves plurals with a three form formula', function (): void {
    $translator = new Translator();
    $translator->addTranslations([
        'domain' => 'messages',
        'plural-forms' => TRANSLATOR_TEST_PLURAL_3,
        'messages' => [
            '' => [
                'One file' => ['Jeden plik', '%d pliki', '%d plików'],
            ],
        ],
    ]);

    expect($translator->ngettext('One file', '%d files', 1))->toBe('Jeden plik');
    expect($translator->ngettext('One file', '%d files', 2))->toBe('%d pliki');
    expect($translator->ngettext('One file', '%d files', 4))->toBe('%d pliki');
    expect($translator->ngettext('One file', '%d files', 5))->toBe('%d plików');
    expect($translator->ngettext('One file', '%d files', 0))->toBe('%d plików');
});

it('combines domain, context and plural lookups', function (): void {
    $translator = new Translator();
    $translator->addTranslations([
        'domain' => 'messages',
        'plural-forms' => TRANSLATOR_TEST_PLURAL_2,
        'messages' => [
            'sidebar' => [
                'One link' => ['Un collegamento', '%d collegamenti'],
            ],
        ],
    ]);

    expect($translator->dnpgettext('messages', 'sidebar', 'One link', '%d links', 1))
        ->toBe('Un collegamento');
    expect($translator->dnpgettext('messages', 'sidebar', 'One link', '%d links', 20))
        ->toBe('%d collegamenti');
});

it('uses a simple fallback rule for missing entries', function (): void {
    $translator = new Translator();
    $translator->addTranslations([
        'domain' => 'messages',
        'plural-forms' => TRANSLATOR_TEST_PLURAL_3,
        'messages' => [],
    ]);

    expect($translator->ngettext('One house', '%d houses', 1))->toBe('One house');
    expect($translator->ngettext('One house', '%d houses', 2))->toBe('%d houses');
    expect($translator->dngettext('other-domain', 'One house', '%d houses', 3))->toBe('%d houses');
});

it('uses the first domain as the default and switches it', function (): void {
    $translator = new Translator();
    $translator->addTranslations([
        'domain' => 'first',
        'messages' => [
            '' => [
                'Color' => 'Colour',
            ],
        ],
    ]);
    $translator->addTranslations([
        'domain' => 'second',
        'messages' => [
            '' => [
                'Color' => 'Colore',
            ],
        ],
    ]);

    expect($translator->gettext('Color'))->toBe('Colour');

    $translator->defaultDomain('second');

    expect($translator->gettext('Color'))->toBe('Colore');
    expect($translator->dgettext('first', 'Color'))->toBe('Colour');
});

it('merges translations added to the same domain', function (): void {
    $translator = new Translator();
    $translator->addTranslations([
        'domain' => 'messages',
        'messages' => [
            '' => [
                'Hello' => 'Ciao',
            ],
        ],
    ]);
    $translator->addTranslations([
        'domain' => 'messages',
        'messages' => [
            '' => [
                'Bye' => 'Arrivederci',
                'Hello' => 'Salve',
            ],
        ],
    ]);

    expect($translator->gettext('Hello'))->toBe('Salve');
    expect($translator->gettext('Bye'))->toBe('Arrivederci');
});

it('loads translations from a file', function (): void {
    $file = sys_get_temp_dir() . '/gettext-translator-test-' . uniqid() . '.php';
    $code = "<?php return " . var_export([
        'domain' => 'filed',
        'messages' => [
            '' => [
                'Key' => 'Valore',
            ],
        ],
    ], true) . ";";
    file_put_contents($file, $code);

    try {
        $translator = new Translator();
        $returned = $translator->loadTranslations($file);

        expect($returned)->toBe($translator);
        expect($translator->gettext('Key'))->toBe('Valore');
    } finally {
        unlink($file);
    }
});

it('rejects translation files that do not return arrays', function (): void {
    $file = sys_get_temp_dir() . '/gettext-translator-test-' . uniqid() . '.php';
    file_put_contents($file, '<?php return "not-an-array";');

    try {
        $translator = new Translator();

        expect(fn () => $translator->loadTranslations($file))
            ->toThrow(InvalidArgumentException::class, 'Invalid translations file: it must return an array');
    } finally {
        unlink($file);
    }
});

it('rejects an invalid plural expression', function (): void {
    $translator = new Translator();

    expect(fn () => $translator->addTranslations([
        'domain' => 'messages',
        'plural-forms' => 'nplurals=2; plural=n @ 1;',
        'messages' => [],
    ]))->toThrow(InvalidArgumentException::class, 'Invalid plural form expression');
});

it('normalizes malformed dictionaries safely', function (): void {
    $translator = new Translator();
    $translator->addTranslations([
        'domain' => 123,
        'messages' => 'not-an-array',
    ]);

    expect($translator->gettext('Anything'))->toBe('Anything');

    $translator->addTranslations([
        'domain' => 'mixed',
        'messages' => [
            'scalar-context' => 'dropped-context',
            '' => [
                'kept' => 'KEPT',
                42 => 123,
                'plurals' => ['PRIMO', 123, 'SECONDO'],
            ],
        ],
    ]);

    expect($translator->dgettext('mixed', 'kept'))->toBe('KEPT');
    expect($translator->dngettext('mixed', 'plurals', 'x', 1))->toBe('PRIMO');
    expect($translator->dngettext('mixed', 'plurals', 'x', 2))->toBe('SECONDO');
    expect($translator->gettext('123'))->toBe('123');
});

it('returns the original unchanged for noop', function (): void {
    $translator = new Translator();

    expect($translator->noop('Just marked'))->toBe('Just marked');
});
