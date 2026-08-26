<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Loader;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Headers;
use Omega\Gettext\Loader\Loader;
use Omega\Gettext\Loader\PoLoader;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;
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

    #[DataProvider('stringDecodeProvider')]
    public function testStringDecode(string $source, string $decoded): void
    {
        $this->assertSame($decoded, PoLoader::decode($source));
    }
}
