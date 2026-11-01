<?php

declare(strict_types=1);

namespace App\Application\Groceries;

use App\Application\Identity\MemberView;
use App\Domain\Groceries\GroceryItem;
use App\Domain\Groceries\GroceryList;
use App\Domain\Shared\Alphabetical;

final readonly class GroceryListView
{
    /**
     * @param list<GroceryItemView> $items
     */
    private function __construct(
        public string $id,
        public string $startedAt,
        public MemberView $startedBy,
        public array $items,
    ) {
    }

    public static function of(GroceryList $list): self
    {
        $items = Alphabetical::sort($list->items(), static fn (GroceryItem $item): string => $item->ingredient()->name(), static fn (GroceryItem $item): string => (string) $item->id());

        return new self(
            (string) $list->id(),
            $list->startedAt()->format(\DateTimeInterface::ATOM),
            new MemberView((string) $list->startedBy()->id(), $list->startedBy()->displayName()),
            array_map(GroceryItemView::of(...), $items),
        );
    }
}
