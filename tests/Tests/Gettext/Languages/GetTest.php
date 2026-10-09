<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Languages;

use Omega\Gettext\Languages\Category;
use Omega\Gettext\Languages\CldrData;
use Omega\Gettext\Languages\FormulaConverter;
use Omega\Gettext\Languages\Language;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Tests\TestCase;

#[CoversClass(Category::class)]
#[CoversClass(CldrData::class)]
#[CoversClass(FormulaConverter::class)]
#[CoversClass(Language::class)]
final class GetTest extends TestCase
{
    public function testReturnsAPlausibleNumberOfLanguages(): void
    {
        $list = Language::getAll();
        $count = count($list);

        $this->assertGreaterThan(100, $count);
        $this->assertLessThan(10000, $count);
    }

    public function testFindsLanguagesById(): void
    {
        $this->assertNull(Language::getById('root'));

        $language = Language::getById('it');
        $this->assertNotNull($language);
        $this->assertInstanceOf(Language::class, $language);
        $this->assertSame('Italian', $language->name);
        $this->assertNull($language->territory);

        $language = Language::getById('it-IT');
        $this->assertNotNull($language);
        $this->assertSame('it_IT', $language->id);
        $this->assertSame('Italian (Italy)', $language->name);
        $this->assertSame('Italy', $language->territory);

        $language = Language::getById('it_IT');
        $this->assertNotNull($language);
        $this->assertSame('it_IT', $language->id);
        $this->assertSame('Italian (Italy)', $language->name);

        $language1 = Language::getById('nl_BE');
        $this->assertNotNull($language1);
        $language2 = Language::getById('nl');
        $this->assertNotNull($language2);
        $this->assertSame($language2->name, $language1->baseLanguage);

        $language = Language::getById('it');
        $this->assertNotNull($language);
        $this->assertNull($language->script);

        $this->assertNull(Language::getById('it_Xxxxx'));

        $language = Language::getById('it_Latn');
        $this->assertNotNull($language);
        $this->assertNotNull($language->script);
    }

    public function testResolvesThePortugueseVariants(): void
    {
        $pt = Language::getById('pt');
        $this->assertNotNull($pt);
        $this->assertSame('Portuguese', $pt->name);
        $this->assertCount(3, $pt->categories);
        $this->assertSame('one', $pt->categories[0]->id);

        $ptPT = Language::getById('pt-PT');
        $this->assertNotNull($ptPT);
        $this->assertSame('European Portuguese', $ptPT->name);
        $this->assertCount(3, $ptPT->categories);
        $this->assertSame('one', $ptPT->categories[0]->id);

        $ptBR = Language::getById('pt-BR');
        $this->assertNotNull($ptBR);
        $this->assertSame('Brazilian Portuguese', $ptBR->name);
        $this->assertCount(3, $ptBR->categories);
        $this->assertSame('one', $ptBR->categories[0]->id);

        $ptCV = Language::getById('pt-CV');
        $this->assertNotNull($ptCV);
        $this->assertSame('Portuguese (Cape Verde)', $ptCV->name);
        $this->assertCount(3, $ptCV->categories);
        $this->assertSame('one', $ptCV->categories[0]->id);

        $this->assertSame($ptBR->formula, $pt->formula);
        $this->assertNotSame($ptPT->formula, $pt->formula);
        $this->assertSame($ptCV->formula, $ptBR->formula);
    }
}
