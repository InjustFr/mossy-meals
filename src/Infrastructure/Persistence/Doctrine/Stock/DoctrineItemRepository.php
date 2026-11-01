<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Stock;

use App\Domain\Shared\Exception\NotFound;
use App\Domain\Stock\Item;
use App\Domain\Stock\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineItemRepository implements ItemRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(Item $item): void
    {
        $this->entityManager->persist($item);
    }

    public function remove(Item $item): void
    {
        $this->entityManager->remove($item);
    }

    public function get(Ulid $id): Item
    {
        return $this->entityManager->find(Item::class, $id) ?? throw new NotFound('item', (string) $id);
    }

    public function named(string $name, ?Ulid $except = null): ?Item
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('i')
            ->from(Item::class, 'i')
            ->where('LOWER(i.name) = :name')
            ->setParameter('name', mb_strtolower(trim($name)))
            ->setMaxResults(1);
        if (null !== $except) {
            $query->andWhere('i.id != :except')->setParameter('except', $except, UlidType::NAME);
        }

        $item = $query->getQuery()->getOneOrNullResult();

        return $item instanceof Item ? $item : null;
    }
}
