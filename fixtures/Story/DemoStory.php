<?php

declare(strict_types=1);

namespace App\Fixtures\Story;

use App\Domain\Cooking\Meal;
use App\Domain\Cooking\MealType;
use App\Domain\Cooking\Need;
use App\Domain\Cooking\Portion;
use App\Domain\Cooking\Recipe;
use App\Domain\Identity\User;
use App\Domain\Stock\Item;
use App\Domain\Stock\Kind;
use App\Domain\Stock\Location;
use App\Domain\Stock\Quantity;
use App\Domain\Stock\Unit;
use App\Fixtures\Factory\ItemFactory;
use App\Fixtures\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Zenstruck\Foundry\Story;

final class DemoStory extends Story
{
    private const int UNIT = 12;

    public function __construct(
        private readonly ClockInterface $clock,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function build(): void
    {
        $now = $this->clock->now();

        $demo = UserFactory::createOne(['accountId' => 'demo', 'email' => 'demo@muhmeal.local', 'displayName' => 'Demo', 'now' => $now]);
        $sam = UserFactory::createOne(['accountId' => 'sam', 'email' => 'sam@muhmeal.local', 'displayName' => 'Sam', 'now' => $now]);

        $kitchen = [];
        foreach ($this->kitchen() as [$name, $kind, $location, $unit, $stock, $manual]) {
            $kitchen[$name] = ItemFactory::createOne(['name' => $name, 'kind' => $kind, 'location' => $location, 'unit' => $unit, 'stock' => $stock, 'manual' => $manual, 'now' => $now]);
        }

        $this->recipes($kitchen, $now);
        $this->pastMeals($kitchen, $demo, $sam, $now);
        $this->entityManager->flush();
    }

    /**
     * @return list<array{string, Kind, Location, Unit, int, bool}>
     */
    private function kitchen(): array
    {
        return [
            ['Butter', Kind::Ingredient, Location::Fridge, Unit::Pack, 6, false],
            ['Cheddar', Kind::Ingredient, Location::Fridge, Unit::Pack, 3, false],
            ['Eggs', Kind::Ingredient, Location::Fridge, Unit::Piece, 8 * self::UNIT, false],
            ['Leeks', Kind::Ingredient, Location::Fridge, Unit::Piece, self::UNIT, false],
            ['Milk', Kind::Ingredient, Location::Fridge, Unit::Carton, 21, false],
            ['Seitan', Kind::Ingredient, Location::Fridge, Unit::Bag, 0, false],
            ['Yoghurt', Kind::Ingredient, Location::Fridge, Unit::Tub, 0, false],
            ['Fish fingers', Kind::Ingredient, Location::Freezer, Unit::Box, self::UNIT, false],
            ['Frozen berries', Kind::Ingredient, Location::Freezer, Unit::Bag, 28, false],
            ['Peas', Kind::Ingredient, Location::Freezer, Unit::Bag, 8, false],
            ['Canned tomatoes', Kind::Ingredient, Location::Pantry, Unit::Can, 4 * self::UNIT, false],
            ['Flour', Kind::Ingredient, Location::Pantry, Unit::Bag, 18, false],
            ['Gluten', Kind::Ingredient, Location::Pantry, Unit::Bag, 2 * self::UNIT, false],
            ['Olive oil', Kind::Ingredient, Location::Pantry, Unit::Bottle, 10, true],
            ['Onions', Kind::Ingredient, Location::Pantry, Unit::Piece, 5 * self::UNIT, false],
            ['Pasta', Kind::Ingredient, Location::Pantry, Unit::Pack, 3 * self::UNIT, false],
            ['Rice', Kind::Ingredient, Location::Pantry, Unit::Bag, 3, false],
            ['Salt', Kind::Ingredient, Location::Pantry, Unit::Box, self::UNIT, true],
            ['Spices', Kind::Ingredient, Location::Pantry, Unit::Jar, 6, true],
            ['Water', Kind::Ingredient, Location::Pantry, Unit::Bottle, 2 * self::UNIT, true],
            ['Toilet paper', Kind::Household, Location::Home, Unit::Pack, 3, false],
        ];
    }

    /**
     * @param array<string, Item> $kitchen
     */
    private function recipes(array $kitchen, \DateTimeImmutable $now): void
    {
        $recipes = [
            Recipe::prep('Seitan', [
                $this->need($kitchen, 'Gluten', self::UNIT),
                $this->need($kitchen, 'Water'),
                $this->need($kitchen, 'Spices'),
            ], $kitchen['Seitan'], Quantity::ofTwelfths(self::UNIT), $now),
            Recipe::meal('Seitan stew', [
                $this->need($kitchen, 'Seitan', self::UNIT),
                $this->need($kitchen, 'Onions', self::UNIT),
                $this->need($kitchen, 'Canned tomatoes', self::UNIT),
                $this->need($kitchen, 'Olive oil', 3),
                $this->need($kitchen, 'Salt'),
            ], $now),
            Recipe::meal('Pasta al pomodoro', [
                $this->need($kitchen, 'Pasta', self::UNIT),
                $this->need($kitchen, 'Canned tomatoes', self::UNIT),
                $this->need($kitchen, 'Onions', self::UNIT),
                $this->need($kitchen, 'Olive oil', 3),
                $this->need($kitchen, 'Salt'),
            ], $now),
            Recipe::meal('Cheese omelette', [
                $this->need($kitchen, 'Eggs', 3 * self::UNIT),
                $this->need($kitchen, 'Cheddar', 3),
                $this->need($kitchen, 'Butter', 3),
            ], $now),
            Recipe::meal('Berry yoghurt bowl', [
                $this->need($kitchen, 'Yoghurt', self::UNIT),
                $this->need($kitchen, 'Frozen berries', 6),
            ], $now),
        ];

        foreach ($recipes as $recipe) {
            $this->entityManager->persist($recipe);
        }
    }

    /**
     * @param array<string, Item> $kitchen
     */
    private function need(array $kitchen, string $name, ?int $twelfths = null): Need
    {
        return new Need($kitchen[$name], null === $twelfths ? null : Quantity::ofTwelfths($twelfths));
    }

    /**
     * @param array<string, Item> $kitchen
     */
    private function pastMeals(array $kitchen, User $demo, User $sam, \DateTimeImmutable $now): void
    {
        $meals = [
            [1, MealType::Dinner, 'Pasta al pomodoro', [$demo, $sam], $demo, ['Pasta' => self::UNIT, 'Canned tomatoes' => self::UNIT, 'Onions' => self::UNIT]],
            [1, MealType::Lunch, 'Leftover pasta', [$sam], $sam, []],
            [2, MealType::Breakfast, 'Pancakes', [$demo, $sam], $sam, ['Flour' => 3, 'Eggs' => 2 * self::UNIT, 'Milk' => 3]],
            [4, MealType::Dinner, null, [$demo], $demo, ['Fish fingers' => self::UNIT, 'Peas' => 6]],
            [6, MealType::Snack, 'Frozen berries', [$sam], $sam, ['Frozen berries' => 3]],
        ];

        foreach ($meals as [$daysAgo, $type, $title, $eaters, $loggedBy, $used]) {
            $at = $now->modify("-$daysAgo days");
            $portions = [];
            foreach ($used as $name => $twelfths) {
                $portions[] = new Portion($kitchen[$name], Quantity::ofTwelfths($twelfths));
            }
            $meal = Meal::log($at, $type, $title, $eaters, $portions, $loggedBy, $at);
            $meal->giveBack();
            $this->entityManager->persist($meal);
        }
    }
}
