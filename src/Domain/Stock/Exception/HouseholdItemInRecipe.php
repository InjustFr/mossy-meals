<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class HouseholdItemInRecipe extends InvalidItem
{
    public function __construct(string $name, string $recipes)
    {
        parent::__construct('item.household_in_recipe', ['name' => $name, 'recipes' => $recipes]);
    }
}
