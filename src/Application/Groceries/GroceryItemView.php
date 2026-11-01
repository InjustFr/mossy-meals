<?php

declare(strict_types=1);

namespace App\Application\Groceries;

use App\Domain\Groceries\GroceryItem;

final readonly class GroceryItemView
{
    private function __construct(
        public string $id,
        public string $itemId,
        public string $name,
        public string $unit,
        public string $location,
        public int $stock,
        public bool $ticked,
    ) {
    }

    public static function of(GroceryItem $item): self
    {
        $stocked = $item->item();

        return new self(
            (string) $item->id(),
            (string) $stocked->id(),
            $stocked->name(),
            $stocked->unit()->value,
            $stocked->location()->value,
            $stocked->stock()->twelfths(),
            $item->isTicked(),
        );
    }
}
