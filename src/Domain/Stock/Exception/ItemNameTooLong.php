<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class ItemNameTooLong extends InvalidItem
{
    public function __construct(int $max)
    {
        parent::__construct('item.name_too_long', ['max' => $max]);
    }
}
