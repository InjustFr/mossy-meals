<?php

declare(strict_types=1);

namespace App\Domain\Cooking;

use App\Domain\Stock\Item;
use App\Domain\Stock\Quantity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'recipe_line')]
#[ORM\UniqueConstraint(columns: ['recipe_id', 'ingredient_id'])]
class RecipeLine
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Recipe::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Recipe $recipe;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Item $ingredient;

    #[ORM\Column(nullable: true)]
    private ?int $amountTwelfths;

    #[ORM\Column(options: ['default' => false])]
    private bool $optional;

    #[ORM\Column]
    private int $position;

    public function __construct(Recipe $recipe, Need $need, int $position)
    {
        $this->id = new Ulid();
        $this->recipe = $recipe;
        $this->ingredient = $need->ingredient;
        $this->measure($need, $position);
    }

    public function measure(Need $need, int $position): void
    {
        $this->amountTwelfths = $need->amount?->twelfths();
        $this->optional = $need->optional;
        $this->position = $position;
    }

    public function ingredient(): Item
    {
        return $this->ingredient;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function amount(): ?Quantity
    {
        return null === $this->amountTwelfths ? null : Quantity::ofTwelfths($this->amountTwelfths);
    }
}
