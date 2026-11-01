<?php

declare(strict_types=1);

namespace App\Fixtures\Factory;

use App\Domain\Stock\Item;
use App\Domain\Stock\Kind;
use App\Domain\Stock\Location;
use App\Domain\Stock\Quantity;
use App\Domain\Stock\Unit;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/** @extends PersistentObjectFactory<Item> */
final class ItemFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Item::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->unique()->word(),
            'kind' => Kind::Ingredient,
            'location' => Location::Pantry,
            'unit' => Unit::Piece,
            'manual' => false,
            'lowAt' => null,
            'stock' => 0,
            'now' => new \DateTimeImmutable(),
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->instantiateWith(Instantiator::namedConstructor('add')->disableHydration())
            ->afterInstantiate(static function (Item $item, array $parameters): void {
                $stock = $parameters['stock'] ?? 0;
                $item->setStock(Quantity::ofTwelfths(\is_int($stock) ? $stock : 0));
            });
    }
}
