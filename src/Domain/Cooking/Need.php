<?php

declare(strict_types=1);

namespace App\Domain\Cooking;

use App\Domain\Cooking\Exception\NotFood;
use App\Domain\Cooking\Exception\ZeroQuantity;
use App\Domain\Pantry\Ingredient;
use App\Domain\Pantry\Quantity;

final readonly class Need
{
    public function __construct(
        public Ingredient $ingredient,
        public ?Quantity $amount,
        public bool $optional = false,
    ) {
        if ($ingredient->isHousehold()) {
            throw new NotFood($ingredient->name());
        }
        if (null !== $amount && $amount->isZero()) {
            throw new ZeroQuantity($ingredient->name());
        }
    }
}
