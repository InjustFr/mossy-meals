<?php

declare(strict_types=1);

namespace App\Application\Groceries\StartGroceryList;

final readonly class StartGroceryList
{
    /**
     * @param list<string> $itemIds
     */
    public function __construct(public array $itemIds)
    {
    }
}
