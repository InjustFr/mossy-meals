<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Cooking;

use App\Application\Cooking\RecipeQueries;
use App\Application\Cooking\RecipeViews;
use App\Domain\Cooking\Recipe;
use App\Domain\Shared\Alphabetical;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineRecipeQueries implements RecipeQueries
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RecipeViews $views,
    ) {
    }

    public function all(): array
    {
        $recipes = $this->entityManager->createQueryBuilder()
            ->select('r', 'l', 'i', 'o', 'm', 'mv', 'iv')
            ->from(Recipe::class, 'r')
            ->leftJoin('r.lines', 'l')
            ->leftJoin('l.ingredient', 'i')
            ->leftJoin('r.output', 'o')
            ->leftJoin('i.variantOf', 'm')
            ->leftJoin('m.variants', 'mv')
            ->leftJoin('i.variants', 'iv')
            ->getQuery()
            ->getResult();

        return $this->views->all(Alphabetical::sort($recipes, static fn (Recipe $recipe): string => $recipe->name(), static fn (Recipe $recipe): string => (string) $recipe->id()));
    }
}
