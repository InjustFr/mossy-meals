<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class EmptyItemName extends InvalidItem
{
    public function __construct()
    {
        parent::__construct('item.empty_name');
    }
}
