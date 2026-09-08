<?php

declare(strict_types=1);

namespace Tests\Tests\Gettext;

use Omega\Gettext\References;

covers(References::class);

it('manages references', function (): void {
    $references = new References();

    expect($references->jsonSerialize())->toBe([]);
    expect($references)->toHaveCount(0);

    $references->add('filename.php', 34);

    expect($references->jsonSerialize())->toBe(['filename.php' => [34]]);
    expect($references)->toHaveCount(1);

    $references->add('filename.php', 34);

    expect($references->jsonSerialize())->toBe(['filename.php' => [34]]);
    expect($references)->toHaveCount(1);

    $references->add('filename.php', 44);

    expect($references->jsonSerialize())->toBe(['filename.php' => [34, 44]]);
    expect($references)->toHaveCount(2);

    foreach ($references as $filename => $lines) {
        expect($filename)->toBe('filename.php');
        expect($lines)->toBe([34, 44]);
    }
});

it('merges references', function (): void {
    $references1 = new References();
    $references2 = new References();

    $references1
        ->add('filename.php', 34)
        ->add('filename.php', 56)
        ->add('filename3.php')
        ->add('filename2.php', 10);

    $references2
        ->add('filename.php', 34)
        ->add('filename.php', 44)
        ->add('filename2.php')
        ->add('filename4.php')
        ->add('filename3.php', 10)
        ->add('5', 10)
        ->add('6');

    $merged = $references1->mergeWith($references2);

    expect($merged)->toHaveCount(8);
    expect($merged->toArray())->toBe([
        'filename.php' => [34, 56, 44],
        'filename3.php' => [10],
        'filename2.php' => [10],
        'filename4.php' => [],
        '5' => [10],
        '6' => [],
    ]);

    expect($merged)->not->toBe($references1);
    expect($merged)->not->toBe($references2);
});

it('creates a references collection from state', function (): void {
    $state = [
        'references' => [
            'filename.php' => [1, 2, 3],
        ],
    ];
    $references = References::__set_state($state);

    expect($references)->toHaveCount(3);
    expect($references->toArray())->toBe($state['references']);
});
