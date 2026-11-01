<?php

declare(strict_types=1);

namespace App\Domain\Groceries;

use App\Domain\Stock\Item;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'grocery_item')]
#[ORM\UniqueConstraint(columns: ['list_id', 'item_id'])]
class GroceryItem
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: GroceryList::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'list_id', nullable: false, onDelete: 'CASCADE')]
    private GroceryList $list;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Item $item;

    #[ORM\Column]
    private bool $ticked = false;

    public function __construct(GroceryList $list, Item $item)
    {
        $this->id = new Ulid();
        $this->list = $list;
        $this->item = $item;
    }

    public function tick(bool $ticked): void
    {
        $this->ticked = $ticked;
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function item(): Item
    {
        return $this->item;
    }

    public function isTicked(): bool
    {
        return $this->ticked;
    }
}
