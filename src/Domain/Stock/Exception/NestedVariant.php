<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class NestedVariant extends InvalidItem
{
    public function __construct(string $name)
    {
        parent::__construct('item.nested_variant', ['name' => $name]);
    }
}
