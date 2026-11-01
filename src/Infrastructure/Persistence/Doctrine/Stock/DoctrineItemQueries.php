<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Stock;

use App\Application\Stock\ItemQueries;
use App\Application\Stock\ItemViews;
use App\Domain\Shared\Alphabetical;
use App\Domain\Stock\Item;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineItemQueries implements ItemQueries
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ItemViews $views,
    ) {
    }

    public function all(): array
    {
        $items = $this->entityManager->createQueryBuilder()
            ->select('i')
            ->from(Item::class, 'i')
            ->getQuery()
            ->getResult();

        return $this->views->all(Alphabetical::sort($items, static fn (Item $item): string => $item->name(), static fn (Item $item): string => (string) $item->id()));
    }
}
