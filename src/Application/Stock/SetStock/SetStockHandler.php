<?php

declare(strict_types=1);

namespace App\Application\Stock\SetStock;

use App\Application\Stock\ItemView;
use App\Application\Stock\ItemViews;
use App\Application\Transaction;
use App\Domain\Stock\ItemRepository;
use App\Domain\Stock\Quantity;

final readonly class SetStockHandler
{
    public function __construct(
        private ItemRepository $items,
        private ItemViews $views,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(SetStock $command): ItemView
    {
        $item = $this->items->get($command->id);
        $item->setStock(Quantity::ofTwelfths($command->stock));
        $this->transaction->commit();

        return $this->views->of($item);
    }
}
