<?php

declare(strict_types=1);

namespace App\Application\Stock;

interface ItemQueries
{
    /**
     * @return list<ItemView>
     */
    public function all(): array;
}
