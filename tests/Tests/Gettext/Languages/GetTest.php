<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Languages;

use Omega\Gettext\Languages\Category;
use Omega\Gettext\Languages\CldrData;
use Omega\Gettext\Languages\FormulaConverter;
use Omega\Gettext\Languages\Language;

covers(Category::class);
covers(CldrData::class);
covers(FormulaConverter::class);
covers(Language::class);

it('returns a plausible number of languages', function (): void {
    $list = Language::getAll();
    $count = count($list);

    expect($count)->toBeGreaterThan(100);
    expect($count)->toBeLessThan(10000);
});

it('finds languages by id', function (): void {
    expect(Language::getById('root'))->toBeNull();

    $language = Language::getById('it');
    $this->assertNotNull($language);
    $this->assertInstanceOf(Language::class, $language);
    expect($language->name)->toBe('Italian');
    expect($language->territory)->toBeNull();

    $language = Language::getById('it-IT');
    $this->assertNotNull($language);
    expect($language->id)->toBe('it_IT');
    expect($language->name)->toBe('Italian (Italy)');
    expect($language->territory)->toBe('Italy');

    $language = Language::getById('it_IT');
    $this->assertNotNull($language);
    expect($language->id)->toBe('it_IT');
    expect($language->name)->toBe('Italian (Italy)');

    $language1 = Language::getById('nl_BE');
    $this->assertNotNull($language1);
    $language2 = Language::getById('nl');
    $this->assertNotNull($language2);
    expect($language1->baseLanguage)->toBe($language2->name);

    $language = Language::getById('it');
    $this->assertNotNull($language);
    expect($language->script)->toBeNull();

    expect(Language::getById('it_Xxxxx'))->toBeNull();

    $language = Language::getById('it_Latn');
    $this->assertNotNull($language);
    expect($language->script)->not->toBeNull();
});

it('resolves the portuguese variants', function (): void {
    $pt = Language::getById('pt');
    $this->assertNotNull($pt);
    expect($pt->name)->toBe('Portuguese');
    expect($pt->categories)->toHaveCount(3);
    expect($pt->categories[0]->id)->toBe('one');

    $ptPT = Language::getById('pt-PT');
    $this->assertNotNull($ptPT);
    expect($ptPT->name)->toBe('European Portuguese');
    expect($ptPT->categories)->toHaveCount(3);
    expect($ptPT->categories[0]->id)->toBe('one');

    $ptBR = Language::getById('pt-BR');
    $this->assertNotNull($ptBR);
    expect($ptBR->name)->toBe('Brazilian Portuguese');
    expect($ptBR->categories)->toHaveCount(3);
    expect($ptBR->categories[0]->id)->toBe('one');

    $ptCV = Language::getById('pt-CV');
    $this->assertNotNull($ptCV);
    expect($ptCV->name)->toBe('Portuguese (Cape Verde)');
    expect($ptCV->categories)->toHaveCount(3);
    expect($ptCV->categories[0]->id)->toBe('one');

    expect($pt->formula)->toBe($ptBR->formula);
    expect($pt->formula)->not->toBe($ptPT->formula);
    expect($ptBR->formula)->toBe($ptCV->formula);
});
