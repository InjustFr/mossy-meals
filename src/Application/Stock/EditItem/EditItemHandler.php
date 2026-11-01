<?php

declare(strict_types=1);

namespace App\Application\Stock\EditItem;

use App\Application\Stock\ItemView;
use App\Application\Stock\ItemViews;
use App\Application\Transaction;
use App\Domain\Cooking\Recipe;
use App\Domain\Cooking\RecipeRepository;
use App\Domain\Stock\Exception\HouseholdItemInRecipe;
use App\Domain\Stock\Exception\ItemNameTaken;
use App\Domain\Stock\ItemRepository;
use App\Domain\Stock\Months;
use App\Domain\Stock\Quantity;

final readonly class EditItemHandler
{
    public function __construct(
        private ItemRepository $items,
        private RecipeRepository $recipes,
        private ItemViews $views,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(EditItem $command): ItemView
    {
        $item = $this->items->get($command->id);
        if (null !== $this->items->named($command->name, $command->id)) {
            throw new ItemNameTaken(trim($command->name));
        }

        $item->describe(
            $command->name,
            $command->kind,
            $command->location,
            $command->unit,
            $command->manual,
            null === $command->lowAt ? null : Quantity::ofTwelfths($command->lowAt),
        );
        $recipes = $item->isHousehold() ? $this->recipes->using($item) : [];
        if ([] !== $recipes) {
            throw new HouseholdItemInRecipe($item->name(), implode(', ', array_map(static fn (Recipe $recipe): string => $recipe->name(), $recipes)));
        }
        $item->buyIn(Months::of($command->months));
        $item->alertWhenLow($command->alert);
        $item->makeVariantOf(null === $command->variantOf ? null : $this->items->get($command->variantOf));
        $this->transaction->commit();

        return $this->views->of($item);
    }
}
