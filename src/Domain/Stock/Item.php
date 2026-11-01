<?php

declare(strict_types=1);

namespace App\Domain\Stock;

use App\Domain\Stock\Exception\EmptyItemName;
use App\Domain\Stock\Exception\HouseholdVariant;
use App\Domain\Stock\Exception\ItemNameTooLong;
use App\Domain\Stock\Exception\NestedVariant;
use App\Domain\Stock\Exception\VariantOfItself;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'item')]
class Item
{
    public const int MAX_NAME_LENGTH = 60;

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\Column(length: self::MAX_NAME_LENGTH)]
    private string $name;

    #[ORM\Column(length: 16, enumType: Kind::class, options: ['default' => 'ingredient'])]
    private Kind $kind;

    #[ORM\Column(length: 16, enumType: Location::class)]
    private Location $location;

    #[ORM\Column(length: 16, enumType: Unit::class)]
    private Unit $unit;

    #[ORM\Column]
    private bool $manual;

    #[ORM\Embedded(class: Quantity::class)]
    private Quantity $stock;

    #[ORM\Embedded(class: Quantity::class)]
    private Quantity $lowAt;

    #[ORM\Column(options: ['default' => true])]
    private bool $alert = true;

    #[ORM\Embedded(class: Months::class)]
    private Months $months;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Item $variantOf = null;

    /** @var Collection<int, Item> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'variantOf')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $variants;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    private function __construct(string $name, Kind $kind, Location $location, Unit $unit, bool $manual, ?Quantity $lowAt, \DateTimeImmutable $now)
    {
        $this->id = new Ulid();
        $this->name = self::validName($name);
        $this->kind = $kind;
        $this->location = $location;
        $this->unit = $unit;
        $this->manual = $manual || Kind::Household === $kind;
        $this->stock = Quantity::zero();
        $this->lowAt = $lowAt ?? $unit->defaultLowAt();
        $this->months = Months::all();
        $this->variants = new ArrayCollection();
        $this->createdAt = $now;
    }

    public static function add(string $name, Kind $kind, Location $location, Unit $unit, bool $manual, ?Quantity $lowAt, \DateTimeImmutable $now): self
    {
        return new self($name, $kind, $location, $unit, $manual, $lowAt, $now);
    }

    public function describe(string $name, Kind $kind, Location $location, Unit $unit, bool $manual, ?Quantity $lowAt): void
    {
        $this->name = self::validName($name);
        $this->kind = $kind;
        $this->location = $location;
        $this->unit = $unit;
        $this->manual = $manual || Kind::Household === $kind;
        $this->lowAt = $lowAt ?? $unit->defaultLowAt();
    }

    public function alertWhenLow(bool $alert): void
    {
        $this->alert = $alert;
    }

    public function alertsWhenLow(): bool
    {
        return $this->alert;
    }

    public function buyIn(Months $months): void
    {
        $this->months = $this->isHousehold() ? Months::all() : $months;
    }

    public function isBoughtIn(int $month): bool
    {
        return $this->months->includes($month);
    }

    public function makeVariantOf(?self $main): void
    {
        if (null !== $main) {
            if ($main === $this) {
                throw new VariantOfItself($this->name);
            }
            if (null !== $main->variantOf || !$this->variants->isEmpty()) {
                throw new NestedVariant($this->name);
            }
        }
        foreach ([$main, ...$this->variants->toArray()] as $relative) {
            if (null !== $relative && ($relative->isHousehold() || $this->isHousehold())) {
                throw new HouseholdVariant($this->name);
            }
        }

        $this->variantOf?->variants->removeElement($this);
        $this->variantOf = $main;
        $main?->variants->add($this);
    }

    /**
     * @return list<Item>
     */
    public function family(): array
    {
        $main = $this->variantOf ?? $this;
        $others = array_filter([$main, ...$main->variants->toArray()], fn (self $item): bool => $item !== $this);

        return [$this, ...array_values($others)];
    }

    public function setStock(Quantity $stock): void
    {
        $this->stock = $stock;
    }

    public function consume(Quantity $amount): void
    {
        $this->stock = $this->stock->minus($amount);
    }

    public function restock(Quantity $amount): void
    {
        $this->stock = $this->stock->plus($amount);
    }

    public function adjust(int $by): void
    {
        $this->stock = Quantity::ofTwelfths(max(0, $this->stock->twelfths() + $by));
    }

    public function isRunningLow(): bool
    {
        return $this->alert && $this->lowAt->atLeast($this->stock);
    }

    public function isHousehold(): bool
    {
        return Kind::Household === $this->kind;
    }

    public function isOut(): bool
    {
        return $this->stock->isZero();
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function kind(): Kind
    {
        return $this->kind;
    }

    public function location(): Location
    {
        return $this->location;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function isManual(): bool
    {
        return $this->manual;
    }

    public function stock(): Quantity
    {
        return $this->stock;
    }

    public function lowAt(): Quantity
    {
        return $this->lowAt;
    }

    public function months(): Months
    {
        return $this->months;
    }

    public function variantOf(): ?self
    {
        return $this->variantOf;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    private static function validName(string $name): string
    {
        $name = trim($name);
        if ('' === $name) {
            throw new EmptyItemName();
        }
        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw new ItemNameTooLong(self::MAX_NAME_LENGTH);
        }

        return $name;
    }
}
