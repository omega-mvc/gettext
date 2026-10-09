<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use LogicException;
use Omega\Gettext\Formatter;
use Omega\Gettext\Translator;
use Omega\Gettext\TranslatorFunctions;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use Tests\Tests\TestCase;

#[CoversClass(Formatter::class)]
#[CoversClass(Translator::class)]
#[CoversClass(TranslatorFunctions::class)]
final class TranslatorFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $reflection = new ReflectionClass(TranslatorFunctions::class);

        foreach (['translator', 'formatter'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $property->setValue(null, null);
        }
    }

    public function testThrowsBeforeATranslatorIsRegistered(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No translator registered, call TranslatorFunctions::register() first');

        TranslatorFunctions::getTranslator();
    }

    public function testThrowsBeforeAFormatterIsRegistered(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No formatter registered, call TranslatorFunctions::register() first');

        TranslatorFunctions::getFormatter();
    }

    public function testStoresTheProvidedInstancesWhenRegistering(): void
    {
        $translator = new Translator();
        $formatter = new Formatter();

        TranslatorFunctions::register($translator, $formatter);

        $this->assertSame($translator, TranslatorFunctions::getTranslator());
        $this->assertSame($formatter, TranslatorFunctions::getFormatter());
    }

    public function testCreatesADefaultFormatterWhenRegisteringWithoutOne(): void
    {
        $translator = new Translator();

        TranslatorFunctions::register($translator);

        $this->assertSame($translator, TranslatorFunctions::getTranslator());
        $this->assertInstanceOf(Formatter::class, TranslatorFunctions::getFormatter());
    }

    public function testReplacesPreviouslyRegisteredInstances(): void
    {
        $first = new Translator();
        $second = new Translator();
        $formatter = new Formatter();

        TranslatorFunctions::register($first);
        TranslatorFunctions::register($second, $formatter);

        $this->assertSame($second, TranslatorFunctions::getTranslator());
        $this->assertSame($formatter, TranslatorFunctions::getFormatter());
    }
}
