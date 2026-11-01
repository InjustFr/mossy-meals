<?php

declare(strict_types=1);

namespace App\Application\Pantry\EditIngredient;

use App\Application\Pantry\IngredientView;
use App\Application\Pantry\IngredientViews;
use App\Application\Transaction;
use App\Domain\Cooking\Recipe;
use App\Domain\Cooking\RecipeRepository;
use App\Domain\Pantry\Exception\HouseholdItemInRecipe;
use App\Domain\Pantry\Exception\IngredientNameTaken;
use App\Domain\Pantry\IngredientRepository;
use App\Domain\Pantry\Months;
use App\Domain\Pantry\Quantity;

final readonly class EditIngredientHandler
{
    public function __construct(
        private IngredientRepository $ingredients,
        private RecipeRepository $recipes,
        private IngredientViews $views,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(EditIngredient $command): IngredientView
    {
        $ingredient = $this->ingredients->get($command->id);
        if (null !== $this->ingredients->named($command->name, $command->id)) {
            throw new IngredientNameTaken(trim($command->name));
        }

        $ingredient->describe(
            $command->name,
            $command->location,
            $command->unit,
            $command->manual,
            null === $command->lowAt ? null : Quantity::ofTwelfths($command->lowAt),
        );
        $recipes = $ingredient->isHousehold() ? $this->recipes->using($ingredient) : [];
        if ([] !== $recipes) {
            throw new HouseholdItemInRecipe($ingredient->name(), implode(', ', array_map(static fn (Recipe $recipe): string => $recipe->name(), $recipes)));
        }
        $ingredient->buyIn(Months::of($command->months));
        $ingredient->alertWhenLow($command->alert);
        $ingredient->makeVariantOf(null === $command->variantOf ? null : $this->ingredients->get($command->variantOf));
        $this->transaction->commit();

        return $this->views->of($ingredient);
    }
}
