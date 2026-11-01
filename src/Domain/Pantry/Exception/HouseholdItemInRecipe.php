<?php

declare(strict_types=1);

namespace App\Domain\Pantry\Exception;

final class HouseholdItemInRecipe extends InvalidIngredient
{
    public function __construct(string $name, string $recipes)
    {
        parent::__construct('ingredient.household_in_recipe', ['name' => $name, 'recipes' => $recipes]);
    }
}
