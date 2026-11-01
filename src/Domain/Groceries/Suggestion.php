<?php

declare(strict_types=1);

namespace App\Domain\Groceries;

use App\Domain\Stock\Item;
use Symfony\Component\Uid\Ulid;

final readonly class Suggestion
{
    /** @var array<string, true> */
    private array $homemade;

    /**
     * @param list<Ulid> $homemade
     */
    public function __construct(array $homemade, private int $month)
    {
        $this->homemade = array_fill_keys(array_map(static fn (Ulid $id): string => (string) $id, $homemade), true);
    }

    public function isHomemade(Item $item): bool
    {
        return isset($this->homemade[(string) $item->id()]);
    }

    public function suggests(Item $item): bool
    {
        return $item->isRunningLow() && !$this->isHomemade($item) && $item->isBoughtIn($this->month);
    }
}
