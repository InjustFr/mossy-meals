<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class ItemNameTaken extends InvalidItem
{
    public function __construct(string $name)
    {
        parent::__construct('item.name_taken', ['name' => $name]);
    }
}
