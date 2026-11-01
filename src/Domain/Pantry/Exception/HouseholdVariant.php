<?php

declare(strict_types=1);

namespace App\Domain\Pantry\Exception;

final class HouseholdVariant extends InvalidIngredient
{
    public function __construct(string $name)
    {
        parent::__construct('ingredient.household_variant', ['name' => $name]);
    }
}
