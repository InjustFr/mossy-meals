<?php

declare(strict_types=1);

namespace App\Domain\Pantry\Exception;

final class MixedVariant extends InvalidIngredient
{
    public function __construct(string $name)
    {
        parent::__construct('ingredient.mixed_variant', ['name' => $name]);
    }
}
