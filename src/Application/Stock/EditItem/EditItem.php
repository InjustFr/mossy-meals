<?php

declare(strict_types=1);

namespace App\Application\Stock\EditItem;

use App\Domain\Stock\Kind;
use App\Domain\Stock\Location;
use App\Domain\Stock\Unit;
use Symfony\Component\Uid\Ulid;

final readonly class EditItem
{
    /**
     * @param list<int> $months
     */
    public function __construct(
        public Ulid $id,
        public string $name,
        public Location $location,
        public Unit $unit,
        public bool $manual,
        public ?int $lowAt = null,
        public array $months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        public ?Ulid $variantOf = null,
        public bool $alert = true,
        public Kind $kind = Kind::Ingredient,
    ) {
    }
}
