<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class ItemInUse extends InvalidItem
{
    public function __construct(string $name, string $recipes)
    {
        parent::__construct('item.in_use', ['name' => $name, 'recipes' => $recipes]);
    }
}
