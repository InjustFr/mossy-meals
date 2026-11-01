<?php

declare(strict_types=1);

namespace App\Domain\Stock;

enum Kind: string
{
    case Ingredient = 'ingredient';
    case Household = 'household';
}
