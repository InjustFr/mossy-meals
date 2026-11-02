<?php

declare(strict_types=1);

namespace App\Application\Groceries\PutAwayGroceries;

use App\Application\Groceries\BoughtInput;
use App\Application\Transaction;
use App\Domain\Groceries\GroceryListRepository;
use App\Domain\Groceries\Purchase;
use App\Domain\Stock\ItemRepository;
use App\Domain\Stock\Quantity;
use Symfony\Component\Uid\Ulid;

final readonly class PutAwayGroceriesHandler
{
    public function __construct(
        private GroceryListRepository $lists,
        private ItemRepository $items,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(PutAwayGroceries $command): void
    {
        $list = $this->lists->open();
        $list->putAway(array_map(
            fn (BoughtInput $input): Purchase => new Purchase($this->items->get(Ulid::fromString($input->itemId)), Quantity::ofTwelfths($input->amount)),
            $command->bought,
        ));
        $this->lists->remove($list);
        $this->transaction->commit();
    }
}
