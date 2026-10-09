<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext\Generator;

use Omega\Gettext\Comments;
use Omega\Gettext\Flags;
use Omega\Gettext\Generator\ArrayGenerator;
use Omega\Gettext\Headers;
use Omega\Gettext\Languages\Category;
use Omega\Gettext\Languages\CldrData;
use Omega\Gettext\Languages\FormulaConverter;
use Omega\Gettext\Languages\Language;
use Omega\Gettext\References;
use Omega\Gettext\Translation;
use Omega\Gettext\Translations;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Tests\TestCase;

#[CoversClass(ArrayGenerator::class)]
#[CoversClass(Comments::class)]
#[CoversClass(Flags::class)]
#[CoversClass(Headers::class)]
#[CoversClass(Category::class)]
#[CoversClass(CldrData::class)]
#[CoversClass(FormulaConverter::class)]
#[CoversClass(Language::class)]
#[CoversClass(References::class)]
#[CoversClass(Translation::class)]
#[CoversClass(Translations::class)]
final class ArrayGeneratorTest extends TestCase
{
    public function testGeneratesAnArrayFromTranslations(): void
    {
        $translations = Translations::create('testingdomain');
        $translations->setLanguage('ru');

        $translation = Translation::create(
            null,
            'Ensure this value has at least %(limit_value)d character (it has %sd).'
        );
        $translations->add($translation);

        $translation = Translation::create(null, '%ss must be unique for %ss %ss.');
        $translation->translation = '%ss mora da bude jedinstven za %ss %ss.';
        $translations->add($translation);

        $translation = Translation::create('other-context', '日本人は日本で話される言語です！');
        $translation->translation = 'singular';
        $translation->translatePlural('plural1', 'plural2', 'plural3');
        $translations->add($translation);

        $array = (new ArrayGenerator())->generateArray($translations);

        $expected = [
            'domain' => 'testingdomain',
            'plural-forms' => 'nplurals=3; plural=(n % 10 == 1 && n % 100 != 11) ? 0 :'
                . ' ((n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 12 || n % 100 > 14)) ? 1 : 2);',
            'messages' => [
                '' => [
                    '%ss must be unique for %ss %ss.' => '%ss mora da bude jedinstven za %ss %ss.',
                ],
                'other-context' => [
                    '日本人は日本で話される言語です！' => ['singular', 'plural1', 'plural2'],
                ],
            ],
        ];

        self::assertSame($expected, $array);

        self::checkFormatting($expected, $translations);
    }

    public function testGeneratesArraysIncludingEmptyTranslations(): void
    {
        $translations = Translations::create('testingdomain');
        $translations->setLanguage('en');

        $translation = Translation::create(null, 'Empty translation included');
        $translation->translation = '';
        $translations->add($translation);

        $translation = Translation::create(null, 'Inexistent translation included');
        $translations->add($translation);

        $array = (new ArrayGenerator(['includeEmpty' => true]))->generateArray($translations);

        $expected = [
            'domain' => 'testingdomain',
            'plural-forms' => 'nplurals=2; plural=n != 1;',
            'messages' => [
                '' => [
                    'Empty translation included' => '',
                    'Inexistent translation included' => null,
                ],
            ],
        ];

        self::assertSame($expected, $array);

        self::checkFormatting($expected, $translations, ['includeEmpty' => true]);
    }

    /**
     * @param array<string, mixed> $expected
     * @param array<string, bool> $otherOptions
     */
    private static function checkFormatting(
        array $expected,
        Translations $translations,
        array $otherOptions = []
    ): void {
        foreach (
            [
            [],
            ['strictTypes' => true],
            ['pretty' => true],
            ['strictTypes' => true, 'pretty' => true],
            ] as $options
        ) {
            $phpCode = (new ArrayGenerator($options + $otherOptions))->generateString($translations);
            if (empty($options['strictTypes'])) {
                Assert::assertStringNotContainsString('declare(strict_types=1);', $phpCode);
            } else {
                Assert::assertStringContainsString('declare(strict_types=1);', $phpCode);
            }
            if (empty($options['pretty'])) {
                Assert::assertStringEndsWith(');', $phpCode);
                $prefix = '<?php ';
            } else {
                Assert::assertStringEndsWith("];\n", $phpCode);
                $prefix = "<?php\n";
            }
            Assert::assertStringStartsWith($prefix, $phpCode);
            $array = eval(substr($phpCode, strlen($prefix)));
            Assert::assertIsArray($array);
            Assert::assertSame($expected, $array);
        }
    }
}
