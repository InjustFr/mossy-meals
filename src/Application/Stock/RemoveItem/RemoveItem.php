<?php

declare(strict_types=1);

namespace App\Application\Stock\RemoveItem;

use Symfony\Component\Uid\Ulid;

final readonly class RemoveItem
{
    public function __construct(public Ulid $id)
    {
    }
}
