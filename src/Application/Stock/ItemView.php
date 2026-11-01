<?php

declare(strict_types=1);

namespace App\Application\Stock;

use App\Domain\Groceries\Suggestion;
use App\Domain\Stock\Item;

final readonly class ItemView
{
    /**
     * @param list<int> $months
     */
    private function __construct(
        public string $id,
        public string $name,
        public string $kind,
        public string $location,
        public string $unit,
        public bool $manual,
        public int $stock,
        public int $lowAt,
        public bool $alert,
        public bool $runningLow,
        public bool $out,
        public bool $homemade,
        public bool $suggested,
        public array $months,
        public ?string $variantOf,
    ) {
    }

    public static function of(Item $item, Suggestion $suggestion): self
    {
        return new self(
            (string) $item->id(),
            $item->name(),
            $item->kind()->value,
            $item->location()->value,
            $item->unit()->value,
            $item->isManual(),
            $item->stock()->twelfths(),
            $item->lowAt()->twelfths(),
            $item->alertsWhenLow(),
            $item->isRunningLow(),
            $item->isOut(),
            $suggestion->isHomemade($item),
            $suggestion->suggests($item),
            $item->months()->toList(),
            null === $item->variantOf() ? null : (string) $item->variantOf()->id(),
        );
    }
}
