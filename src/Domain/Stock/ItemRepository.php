<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use Symfony\Component\Uid\Ulid;

interface ItemRepository
{
    public function add(Item $item): void;

    public function remove(Item $item): void;

    public function get(Ulid $id): Item;

    public function named(string $name, ?Ulid $except = null): ?Item;
}
