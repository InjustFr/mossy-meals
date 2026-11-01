<?php

declare(strict_types=1);

namespace App\Domain\Stock\Exception;

final class InvalidMonth extends InvalidItem
{
    public function __construct(int $month)
    {
        parent::__construct('item.invalid_month', ['month' => $month]);
    }
}
