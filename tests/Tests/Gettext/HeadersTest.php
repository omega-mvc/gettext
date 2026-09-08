<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use InvalidArgumentException;
use Omega\Gettext\Headers;

covers(Headers::class);

it('manages headers', function (): void {
    $headers = new Headers();

    expect($headers->jsonSerialize())->toBe([]);
    expect($headers)->toHaveCount(0);

    $headers->set('foo', 'bar');

    expect($headers->jsonSerialize())->toBe(['foo' => 'bar']);
    expect($headers)->toHaveCount(1);
    expect($headers->get('foo'))->toBe('bar');

    $headers->set('foo', 'bar2');

    expect($headers->jsonSerialize())->toBe(['foo' => 'bar2']);
    expect($headers)->toHaveCount(1);
    expect($headers->get('foo'))->toBe('bar2');

    $headers->set('foo2', 'bar2');

    expect($headers->jsonSerialize())->toBe(['foo' => 'bar2', 'foo2' => 'bar2']);
    expect($headers)->toHaveCount(2);
    expect($headers->get('foo2'))->toBe('bar2');

    $headers->delete('foo2');
    expect($headers)->toHaveCount(1);

    $headers->clear();
    expect($headers)->toHaveCount(0);
});

it('manages the domain header', function (): void {
    $headers = new Headers();
    $headers->setDomain('foo');

    expect($headers)->toHaveCount(1);
    expect(Headers::HEADER_DOMAIN)->toBe('X-Domain');
    expect($headers->get(Headers::HEADER_DOMAIN))->toBe('foo');
    expect($headers->getDomain())->toBe('foo');
});

it('manages the language header', function (): void {
    $headers = new Headers();
    $headers->setLanguage('gl_ES');

    expect($headers)->toHaveCount(1);
    expect(Headers::HEADER_LANGUAGE)->toBe('Language');
    expect($headers->get(Headers::HEADER_LANGUAGE))->toBe('gl_ES');
    expect($headers->getLanguage())->toBe('gl_ES');
});

it('rejects an invalid plural model count', function (): void {
    $headers = new Headers();

    expect(fn () => $headers->setPluralForm(1, 'foo'))
        ->toThrow(InvalidArgumentException::class);
});

it('manages the plural form header', function (): void {
    $headers = new Headers();
    $headers->setPluralForm(2, '(n=1)');

    expect($headers)->toHaveCount(1);
    expect(Headers::HEADER_PLURAL)->toBe('Plural-Forms');
    expect($headers->get(Headers::HEADER_PLURAL))->toBe('nplurals=2; plural=(n=1);');
    expect($headers->getPluralForm())->toBe([2, '(n=1)']);
});

it('merges headers', function (): void {
    $headers1 = new Headers(['X-Domain' => 'foo', 'Language' => 'gl_ES']);
    $headers2 = new Headers(['Translator' => 'Oscar Otero', 'Language' => 'ru']);
    $merged = $headers1->mergeWith($headers2);

    expect($merged)->toHaveCount(3);
    expect($merged->get('X-Domain'))->toBe('foo');
    expect($merged->get('Translator'))->toBe('Oscar Otero');
    expect($merged->get('Language'))->toBe('ru');

    expect($merged)->not->toBe($headers1);
    expect($merged)->not->toBe($headers2);
});

it('creates a headers collection from state', function (): void {
    $state = ['headers' => ['X-Domain' => 'foo']];
    $headers = Headers::__set_state($state);

    expect($headers)->toHaveCount(1);
    expect($headers->get('X-Domain'))->toBe('foo');
});
