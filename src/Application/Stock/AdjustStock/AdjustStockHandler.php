<?php

declare(strict_types=1);

namespace App\Application\Stock\AdjustStock;

use App\Application\Stock\ItemView;
use App\Application\Stock\ItemViews;
use App\Application\Transaction;
use App\Domain\Stock\ItemRepository;

final readonly class AdjustStockHandler
{
    public function __construct(
        private ItemRepository $items,
        private ItemViews $views,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(AdjustStock $command): ItemView
    {
        $item = $this->items->get($command->id);
        $item->adjust($command->by);
        $this->transaction->commit();

        return $this->views->of($item);
    }
}
