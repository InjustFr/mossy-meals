<?php

declare(strict_types=1);

namespace App\Domain\Groceries;

use App\Domain\Cooking\Exception\IngredientListedTwice;
use App\Domain\Cooking\Exception\ZeroQuantity;
use App\Domain\Stock\Item;
use App\Domain\Stock\Quantity;

final readonly class Purchase
{
    public function __construct(
        public Item $item,
        public Quantity $amount,
    ) {
        if ($amount->isZero()) {
            throw new ZeroQuantity($item->name());
        }
    }

    /**
     * @param list<self> $purchases
     */
    public static function assertDistinct(array $purchases): void
    {
        $seen = [];
        foreach ($purchases as $purchase) {
            $id = (string) $purchase->item->id();
            if (isset($seen[$id])) {
                throw new IngredientListedTwice($purchase->item->name());
            }
            $seen[$id] = true;
        }
    }
}
