<?php

declare(strict_types=1);

namespace App\Presentation\Api\Stock;

use App\Domain\Stock\Item;
use App\Domain\Stock\Kind;
use App\Domain\Stock\Location;
use App\Domain\Stock\Quantity;
use App\Domain\Stock\Unit;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ItemPayload
{
    /**
     * @param list<int> $months
     */
    public function __construct(
        public Location $location,
        public Unit $unit,
        #[Assert\NotBlank(message: 'name.required', normalizer: 'trim')]
        #[Assert\Length(max: Item::MAX_NAME_LENGTH, maxMessage: 'name.too_long', normalizer: 'trim')]
        public string $name = '',
        public bool $manual = false,
        #[Assert\Range(min: 0, max: Quantity::MAX_TWELFTHS, notInRangeMessage: 'quantity.range')]
        public ?int $lowAt = null,
        #[Assert\All([new Assert\Range(min: 1, max: 12, notInRangeMessage: 'month.range')])]
        public array $months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        #[Assert\Ulid(message: 'id.invalid')]
        public ?string $variantOf = null,
        public bool $alert = true,
        public Kind $kind = Kind::Ingredient,
    ) {
    }
}
