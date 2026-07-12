<?php

declare(strict_types=1);

namespace Tests\Gettext\Loader;

use Gettext\Comments;
use Gettext\Flags;
use Gettext\Headers;
use Gettext\Loader\Loader;
use Gettext\Loader\PoLoader;
use Gettext\References;
use Gettext\Translation;
use Gettext\Translations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
#[CoversClass(Loader::class)]
#[CoversClass(PoLoader::class)]
class PoLoaderTest extends BasePoLoaderTestCase
{
    protected function createPoLoader(): Loader
    {
        return new PoLoader();
    }

    /**
     * @param mixed $source
     * @param mixed $decoded
     */
    #[DataProvider('stringDecodeProvider')]
    public function testStringDecode($source, $decoded): void
    {
        $this->assertSame($decoded, PoLoader::decode($source));
    }
}
