<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Cooking;

use App\Domain\Cooking\Recipe;
use App\Domain\Cooking\RecipeLine;
use App\Domain\Cooking\RecipeRepository;
use App\Domain\Shared\Alphabetical;
use App\Domain\Shared\Exception\NotFound;
use App\Domain\Stock\Item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final readonly class DoctrineRecipeRepository implements RecipeRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(Recipe $recipe): void
    {
        $this->entityManager->persist($recipe);
    }

    public function remove(Recipe $recipe): void
    {
        $this->entityManager->remove($recipe);
    }

    public function get(Ulid $id): Recipe
    {
        return $this->entityManager->find(Recipe::class, $id) ?? throw new NotFound('recipe', (string) $id);
    }

    public function named(string $name, ?Ulid $except = null): ?Recipe
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(Recipe::class, 'r')
            ->where('LOWER(r.name) = :name')
            ->setParameter('name', mb_strtolower(trim($name)))
            ->setMaxResults(1);
        if (null !== $except) {
            $query->andWhere('r.id != :except')->setParameter('except', $except, UlidType::NAME);
        }

        $recipe = $query->getQuery()->getOneOrNullResult();

        return $recipe instanceof Recipe ? $recipe : null;
    }

    public function using(Item $ingredient): array
    {
        return self::byName($this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(Recipe::class, 'r')
            ->where('r.output = :ingredient')
            ->orWhere('EXISTS (SELECT l.id FROM '.RecipeLine::class.' l WHERE l.recipe = r AND l.ingredient = :ingredient)')
            ->setParameter('ingredient', $ingredient->id(), UlidType::NAME)
            ->getQuery()
            ->getResult());
    }

    public function outputIds(): array
    {
        return array_map(
            static fn (Item $output): Ulid => $output->id(),
            $this->entityManager->createQueryBuilder()
                ->select('o')
                ->from(Item::class, 'o')
                ->where('EXISTS (SELECT r.id FROM '.Recipe::class.' r WHERE r.output = o)')
                ->getQuery()
                ->getResult(),
        );
    }

    public function preps(): array
    {
        return self::byName($this->entityManager->createQueryBuilder()
            ->select('r', 'l', 'i', 'o', 'm', 'mv', 'iv')
            ->from(Recipe::class, 'r')
            ->join('r.output', 'o')
            ->leftJoin('r.lines', 'l')
            ->leftJoin('l.ingredient', 'i')
            ->leftJoin('i.variantOf', 'm')
            ->leftJoin('m.variants', 'mv')
            ->leftJoin('i.variants', 'iv')
            ->getQuery()
            ->getResult());
    }

    /**
     * @param list<Recipe> $recipes
     *
     * @return list<Recipe>
     */
    private static function byName(array $recipes): array
    {
        return Alphabetical::sort($recipes, static fn (Recipe $recipe): string => $recipe->name(), static fn (Recipe $recipe): string => (string) $recipe->id());
    }
}
