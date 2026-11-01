<?php

declare(strict_types=1);

namespace App\Domain\Cooking;

use App\Domain\Cooking\Exception\IngredientListedTwice;
use App\Domain\Cooking\Exception\NotFood;
use App\Domain\Cooking\Exception\ZeroQuantity;
use App\Domain\Pantry\Ingredient;
use App\Domain\Pantry\Quantity;

final readonly class Portion
{
    public function __construct(
        public Ingredient $ingredient,
        public Quantity $amount,
    ) {
        if ($ingredient->isHousehold()) {
            throw new NotFood($ingredient->name());
        }
        if ($amount->isZero()) {
            throw new ZeroQuantity($ingredient->name());
        }
    }

    /**
     * @param list<self> $portions
     */
    public static function assertDistinct(array $portions): void
    {
        $seen = [];
        foreach ($portions as $portion) {
            $id = (string) $portion->ingredient->id();
            if (isset($seen[$id])) {
                throw new IngredientListedTwice($portion->ingredient->name());
            }
            $seen[$id] = true;
        }
    }
}
