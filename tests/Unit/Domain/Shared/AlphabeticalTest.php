<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared;

use App\Domain\Shared\Alphabetical;
use PHPUnit\Framework\TestCase;

final class AlphabeticalTest extends TestCase
{
    public function testAccentsAndLigaturesSortWithTheirLetters(): void
    {
        $names = ['Zeste', 'Oignon', 'œufs', 'épices', 'Eau', 'Pâtes', 'Farine', 'olive'];

        self::assertSame(
            ['Eau', 'épices', 'Farine', 'œufs', 'Oignon', 'olive', 'Pâtes', 'Zeste'],
            Alphabetical::sort($names, static fn (string $name): string => $name, static fn (string $name): string => $name),
        );
    }

    public function testTheSameNameFallsBackOnTheId(): void
    {
        $items = [['Sam', 'b'], ['Sam', 'a']];

        self::assertSame([['Sam', 'a'], ['Sam', 'b']], Alphabetical::sort($items, static fn (array $item): string => $item[0], static fn (array $item): string => $item[1]));
    }
}
