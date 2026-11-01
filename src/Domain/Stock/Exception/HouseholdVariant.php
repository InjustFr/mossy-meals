<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class HouseholdVariant extends InvalidItem
{
    public function __construct(string $name)
    {
        parent::__construct('item.household_variant', ['name' => $name]);
    }
}
