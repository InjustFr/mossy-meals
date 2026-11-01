<?php

declare(strict_types=1);

namespace App\Application\Stock\RemoveItem;

use App\Application\Transaction;
use App\Domain\Cooking\Recipe;
use App\Domain\Cooking\RecipeRepository;
use App\Domain\Stock\Exception\ItemInUse;
use App\Domain\Stock\ItemRepository;

final readonly class RemoveItemHandler
{
    public function __construct(
        private ItemRepository $items,
        private RecipeRepository $recipes,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(RemoveItem $command): void
    {
        $item = $this->items->get($command->id);
        $recipes = $this->recipes->using($item);
        if ([] !== $recipes) {
            throw new ItemInUse($item->name(), implode(', ', array_map(static fn (Recipe $recipe): string => $recipe->name(), $recipes)));
        }

        $this->items->remove($item);
        $this->transaction->commit();
    }
}
