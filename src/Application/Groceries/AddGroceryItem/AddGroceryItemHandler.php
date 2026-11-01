<?php

declare(strict_types=1);

namespace App\Application\Groceries\AddGroceryItem;

use App\Application\Groceries\GroceryListView;
use App\Application\Transaction;
use App\Domain\Groceries\GroceryListRepository;
use App\Domain\Stock\ItemRepository;

final readonly class AddGroceryItemHandler
{
    public function __construct(
        private GroceryListRepository $lists,
        private ItemRepository $items,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(AddGroceryItem $command): GroceryListView
    {
        $list = $this->lists->open();
        $list->add($this->items->get($command->itemId));
        $this->transaction->commit();

        return GroceryListView::of($list);
    }
}
