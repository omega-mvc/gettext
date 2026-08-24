<?php

declare(strict_types=1);

namespace Tests\Gettext\Languages;

use Exception;
use Gettext\Languages\Category;
use Gettext\Languages\FormulaConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Category::class)]
#[CoversClass(FormulaConverter::class)]
class CategoryTest extends TestCase
{
    public function testConstructorWithInvalidClderId()
    {
        $this->expectException(Exception::class);
        new Category('invalid-cldr-category', 'i = 1 and v = 0 @integer 1');
    }

    public function testConstructorOnCldrIdIsNotInList()
    {
        $this->expectException(Exception::class);
        new Category('pluralRule-count-10000000', 'i = 1 and v = 0 @integer 1');
    }

    public function testConstructorWithInvalidCldrRule()
    {
        $this->expectException(Exception::class);
        new Category('pluralRule-count-one', 'invalid category rule');
    }

    public function testGetExampleIntegers()
    {
        $category = new Category('pluralRule-count-one', 'i = 1 and v = 0 @integer 1');
        $this->assertSame(array(1), $category->getExampleIntegers());
    }
}
