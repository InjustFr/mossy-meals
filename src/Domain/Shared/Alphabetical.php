<?php

declare(strict_types=1);

namespace App\Domain\Shared;

final class Alphabetical
{
    /**
     * @template T
     *
     * @param list<T>             $items
     * @param callable(T): string $name
     * @param callable(T): string $id
     *
     * @return list<T>
     */
    public static function sort(array $items, callable $name, callable $id): array
    {
        $collator = new \Collator('und');
        usort($items, static fn (mixed $a, mixed $b): int => (int) $collator->compare($name($a), $name($b)) ?: $id($a) <=> $id($b));

        return $items;
    }
}
