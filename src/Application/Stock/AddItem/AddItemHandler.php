<?php

declare(strict_types=1);

namespace App\Application\Stock\AddItem;

use App\Application\Stock\ItemView;
use App\Application\Stock\ItemViews;
use App\Application\Transaction;
use App\Domain\Stock\Exception\ItemNameTaken;
use App\Domain\Stock\Item;
use App\Domain\Stock\ItemRepository;
use App\Domain\Stock\Months;
use App\Domain\Stock\Quantity;
use Psr\Clock\ClockInterface;

final readonly class AddItemHandler
{
    public function __construct(
        private ItemRepository $items,
        private ItemViews $views,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(AddItem $command): ItemView
    {
        if (null !== $this->items->named($command->name)) {
            throw new ItemNameTaken(trim($command->name));
        }

        $item = Item::add(
            $command->name,
            $command->kind,
            $command->location,
            $command->unit,
            $command->manual,
            null === $command->lowAt ? null : Quantity::ofTwelfths($command->lowAt),
            $this->clock->now(),
        );
        $item->setStock(Quantity::ofTwelfths($command->stock));
        $item->buyIn(Months::of($command->months));
        $item->alertWhenLow($command->alert);
        $item->makeVariantOf(null === $command->variantOf ? null : $this->items->get($command->variantOf));
        $this->items->add($item);
        $this->transaction->commit();

        return $this->views->of($item);
    }
}
