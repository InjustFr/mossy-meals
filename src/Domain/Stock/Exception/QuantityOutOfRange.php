<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class QuantityOutOfRange extends InvalidItem
{
    public function __construct(int $maxUnits)
    {
        parent::__construct('item.quantity_out_of_range', ['max' => $maxUnits]);
    }
}
