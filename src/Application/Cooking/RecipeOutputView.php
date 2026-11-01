<?php

declare(strict_types=1);

namespace App\Application\Cooking;

use App\Domain\Stock\Item;
use App\Domain\Stock\Quantity;

final readonly class RecipeOutputView
{
    public function __construct(
        public string $ingredientId,
        public string $name,
        public string $unit,
        public int $yield,
        public int $stock,
    ) {
    }

    public static function of(Item $ingredient, Quantity $yield): self
    {
        return new self((string) $ingredient->id(), $ingredient->name(), $ingredient->unit()->value, $yield->twelfths(), $ingredient->stock()->twelfths());
    }
}
