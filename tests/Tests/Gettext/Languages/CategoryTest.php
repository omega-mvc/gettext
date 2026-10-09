<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Languages;

use Exception;
use Omega\Gettext\Languages\Category;
use Omega\Gettext\Languages\FormulaConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Tests\TestCase;

#[CoversClass(Category::class)]
#[CoversClass(FormulaConverter::class)]
final class CategoryTest extends TestCase
{
    public function testRejectsAConstructorWithAnInvalidCldrId(): void
    {
        $this->expectException(Exception::class);

        new Category('invalid-cldr-category', 'i = 1 and v = 0 @integer 1');
    }

    public function testRejectsAConstructorWhoseCldrIdIsNotInTheList(): void
    {
        $this->expectException(Exception::class);

        new Category('pluralRule-count-10000000', 'i = 1 and v = 0 @integer 1');
    }

    public function testRejectsAConstructorWithAnInvalidCldrRule(): void
    {
        $this->expectException(Exception::class);

        new Category('pluralRule-count-one', 'invalid category rule');
    }

    public function testReturnsTheExampleIntegers(): void
    {
        $category = new Category('pluralRule-count-one', 'i = 1 and v = 0 @integer 1');

        $this->assertSame([1], $category->getExampleIntegers());
    }
}
