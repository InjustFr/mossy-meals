<?php

declare(strict_types=1);

namespace App\Application\Stock;

use App\Domain\Cooking\RecipeRepository;
use App\Domain\Groceries\Suggestion;
use App\Domain\Stock\Item;
use Psr\Clock\ClockInterface;

final readonly class ItemViews
{
    public function __construct(
        private RecipeRepository $recipes,
        private ClockInterface $clock,
    ) {
    }

    public function of(Item $item): ItemView
    {
        return ItemView::of($item, $this->suggestion());
    }

    /**
     * @param list<Item> $items
     *
     * @return list<ItemView>
     */
    public function all(array $items): array
    {
        $suggestion = $this->suggestion();

        return array_map(static fn (Item $item): ItemView => ItemView::of($item, $suggestion), $items);
    }

    private function suggestion(): Suggestion
    {
        return new Suggestion($this->recipes->outputIds(), (int) $this->clock->now()->format('n'));
    }
}
