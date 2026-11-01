<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class VariantOfItself extends InvalidItem
{
    public function __construct(string $name)
    {
        parent::__construct('item.variant_of_itself', ['name' => $name]);
    }
}
