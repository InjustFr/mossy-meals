<?php

declare(strict_types=1);

namespace App\Domain\Cooking\Exception;

final class NotFood extends InvalidCooking
{
    public function __construct(string $ingredient)
    {
        parent::__construct('cooking.not_food', ['ingredient' => $ingredient]);
    }
}
