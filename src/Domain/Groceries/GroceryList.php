<?php

declare(strict_types=1);

namespace App\Domain\Groceries;

use App\Domain\Cooking\Portion;
use App\Domain\Groceries\Exception\EmptyGroceryList;
use App\Domain\Identity\User;
use App\Domain\Shared\Exception\NotFound;
use App\Domain\Stock\Item;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'grocery_list')]
class GroceryList
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\Column(name: 'open', unique: true)]
    private bool $open = true;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $startedBy;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    /** @var Collection<int, GroceryItem> */
    #[ORM\OneToMany(targetEntity: GroceryItem::class, mappedBy: 'list', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    /**
     * @param list<Item> $items
     */
    public function __construct(array $items, User $startedBy, \DateTimeImmutable $now)
    {
        if ([] === $items) {
            throw new EmptyGroceryList();
        }

        $this->id = new Ulid();
        $this->startedBy = $startedBy;
        $this->startedAt = $now;
        $this->items = new ArrayCollection();
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(Item $item): void
    {
        foreach ($this->items as $listed) {
            if ($listed->item()->id()->equals($item->id())) {
                return;
            }
        }

        $this->items->add(new GroceryItem($this, $item));
    }

    public function tick(Ulid $itemId, bool $ticked): void
    {
        foreach ($this->items as $item) {
            if ($item->id()->equals($itemId)) {
                $item->tick($ticked);

                return;
            }
        }

        throw new NotFound('grocery_item', (string) $itemId);
    }

    /**
     * @param list<Portion> $bought
     */
    public function putAway(array $bought): void
    {
        Portion::assertDistinct($bought);

        foreach ($bought as $portion) {
            $portion->ingredient->restock($portion->amount);
        }
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function startedBy(): User
    {
        return $this->startedBy;
    }

    public function startedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    /**
     * @return list<GroceryItem>
     */
    public function items(): array
    {
        return array_values($this->items->toArray());
    }
}
