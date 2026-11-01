<?php

declare(strict_types=1);

namespace App\Tests\Functional\Presentation;

use App\Domain\Cooking\Exception\EmptyMeal;
use App\Domain\Cooking\Exception\EmptyRecipeName;
use App\Domain\Cooking\Exception\IngredientListedTwice;
use App\Domain\Cooking\Exception\MealNeedsEater;
use App\Domain\Cooking\Exception\MealTitleTooLong;
use App\Domain\Cooking\Exception\NotAPrep;
use App\Domain\Cooking\Exception\NotFood;
use App\Domain\Cooking\Exception\RecipeNameTaken;
use App\Domain\Cooking\Exception\RecipeNameTooLong;
use App\Domain\Cooking\Exception\RecipeNeedsIngredient;
use App\Domain\Cooking\Exception\ZeroQuantity;
use App\Domain\Cooking\Exception\ZeroYield;
use App\Domain\Groceries\Exception\EmptyGroceryList;
use App\Domain\Groceries\Exception\GroceryListAlreadyOpen;
use App\Domain\Identity\Exception\EmailAlreadyTaken;
use App\Domain\Identity\Exception\EmptyDisplayName;
use App\Domain\Identity\Exception\InvalidEmail;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\Exception\InvalidDay;
use App\Domain\Shared\Exception\NotFound;
use App\Domain\Stock\Exception\EmptyItemName;
use App\Domain\Stock\Exception\HouseholdItemInRecipe;
use App\Domain\Stock\Exception\HouseholdVariant;
use App\Domain\Stock\Exception\InvalidMonth;
use App\Domain\Stock\Exception\ItemInUse;
use App\Domain\Stock\Exception\ItemNameTaken;
use App\Domain\Stock\Exception\ItemNameTooLong;
use App\Domain\Stock\Exception\NestedVariant;
use App\Domain\Stock\Exception\QuantityOutOfRange;
use App\Domain\Stock\Exception\VariantOfItself;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ExceptionMessagesTest extends KernelTestCase
{
    #[DataProvider('exceptions')]
    public function testEveryRuleViolationSpeaksEnglishAndFrench(DomainException $exception, string $english, string $french): void
    {
        $translator = self::getContainer()->get(TranslatorInterface::class);

        self::assertSame($english, $translator->trans($exception->getMessage(), $exception->parameters(), 'exceptions', 'en'));
        self::assertSame($french, $translator->trans($exception->getMessage(), $exception->parameters(), 'exceptions', 'fr'));
    }

    /** @return iterable<array{DomainException, string, string}> */
    public static function exceptions(): iterable
    {
        yield 'invalid month' => [new InvalidMonth(13), '13 is not a month: a month goes from 1 to 12.', '13 n’est pas un mois : un mois va de 1 à 12.'];
        yield 'variant of itself' => [new VariantOfItself('Zucchini'), '“Zucchini” cannot be a variant of itself.', '« Zucchini » ne peut pas être une variante de lui-même.'];
        yield 'nested variant' => [new NestedVariant('Zucchini'), '“Zucchini” cannot be a variant: variants go one level deep, under a main ingredient.', '« Zucchini » ne peut pas être une variante : les variantes n’ont qu’un niveau, sous un ingrédient principal.'];
        yield 'household variant' => [new HouseholdVariant('Soap'), '“Soap” cannot be in a family of variants: household items have no variants.', '« Soap » ne peut pas faire partie d’une famille de variantes : les articles de maison n’ont pas de variantes.'];
        yield 'household item in recipe' => [new HouseholdItemInRecipe('Pasta', 'Aglio'), '“Pasta” cannot be a household item: it is used by Aglio.', '« Pasta » ne peut pas devenir un article de maison : il est utilisé par Aglio.'];
        yield 'not food' => [new NotFood('Soap'), '“Soap” is a household item, not an ingredient.', '« Soap » est un article de maison, pas un ingrédient.'];
        yield 'invalid day' => [new InvalidDay('2026-02-30'), '“2026-02-30” is not a valid date.', '« 2026-02-30 » n’est pas une date valide.'];
        yield 'missing user' => [new NotFound('user', 'abc'), 'This person no longer exists.', 'Cette personne n’existe plus.'];
        yield 'missing item' => [new NotFound('item', 'abc'), 'This item no longer exists.', 'Cet article n’existe plus.'];
        yield 'missing recipe' => [new NotFound('recipe', 'abc'), 'This recipe no longer exists.', 'Cette recette n’existe plus.'];
        yield 'missing meal' => [new NotFound('meal', 'abc'), 'This meal no longer exists.', 'Ce repas n’existe plus.'];
        yield 'missing grocery list' => [new NotFound('grocery_list', 'open'), 'This grocery list no longer exists.', 'Cette liste de courses n’existe plus.'];
        yield 'missing grocery item' => [new NotFound('grocery_item', 'abc'), 'This grocery item no longer exists.', 'Cet article n’existe plus.'];
        yield 'missing anything else' => [new NotFound('widget', 'abc'), 'This item no longer exists.', 'Cet élément n’existe plus.'];
        yield 'invalid email' => [new InvalidEmail('lou'), '“lou” is not a valid email address.', '« lou » n’est pas une adresse e-mail valide.'];
        yield 'empty display name' => [new EmptyDisplayName(), 'Tell us what to call you.', 'Dites-nous comment vous appeler.'];
        yield 'email taken' => [new EmailAlreadyTaken('lou@example.com'), 'An account already uses lou@example.com. Sign in instead.', 'Un compte utilise déjà lou@example.com. Connectez-vous plutôt.'];
        yield 'empty item name' => [new EmptyItemName(), 'Give the item a name.', 'Donnez un nom à l’article.'];
        yield 'long item name' => [new ItemNameTooLong(60), 'An item name has at most 60 characters.', 'Un nom d’article compte au plus 60 caractères.'];
        yield 'item name taken' => [new ItemNameTaken('Milk'), 'There is already an item called “Milk”.', 'Il existe déjà un article nommé « Milk ».'];
        yield 'quantity out of range' => [new QuantityOutOfRange(999), 'A quantity goes from 0 to 999 units.', 'Une quantité va de 0 à 999 unités.'];
        yield 'item in use' => [new ItemInUse('Pasta', 'Aglio, Soup'), '“Pasta” cannot be deleted: it is used by Aglio, Soup.', '« Pasta » ne peut pas être supprimé : il est utilisé par Aglio, Soup.'];
        yield 'empty recipe name' => [new EmptyRecipeName(), 'Give the recipe a name.', 'Donnez un nom à la recette.'];
        yield 'long recipe name' => [new RecipeNameTooLong(80), 'A recipe name has at most 80 characters.', 'Un nom de recette compte au plus 80 caractères.'];
        yield 'recipe name taken' => [new RecipeNameTaken('Soup'), 'There is already a recipe called “Soup”.', 'Il existe déjà une recette nommée « Soup ».'];
        yield 'recipe without ingredient' => [new RecipeNeedsIngredient(), 'A recipe needs at least one ingredient.', 'Une recette demande au moins un ingrédient.'];
        yield 'ingredient listed twice' => [new IngredientListedTwice('Pasta'), '“Pasta” is listed twice.', '« Pasta » apparaît deux fois.'];
        yield 'zero quantity' => [new ZeroQuantity('Pasta'), 'The amount of “Pasta” has to be more than zero.', 'La quantité de « Pasta » doit être supérieure à zéro.'];
        yield 'zero yield' => [new ZeroYield(), 'A prep has to make more than zero.', 'Une préparation doit produire plus de zéro.'];
        yield 'not a prep' => [new NotAPrep('Pasta'), '“Pasta” makes a meal, not an ingredient.', '« Pasta » fait un repas, pas un ingrédient.'];
        yield 'meal without eater' => [new MealNeedsEater(), 'Someone has to eat the meal.', 'Quelqu’un doit manger ce repas.'];
        yield 'empty meal' => [new EmptyMeal(), 'Give the meal a title or at least one ingredient.', 'Donnez un titre au repas ou au moins un ingrédient.'];
        yield 'grocery list already open' => [new GroceryListAlreadyOpen(), 'A shopping trip is already open. Finish it first.', 'Une course est déjà ouverte. Terminez-la d’abord.'];
        yield 'empty grocery list' => [new EmptyGroceryList(), 'Pick at least one item to shop for.', 'Choisissez au moins un article à acheter.'];
        yield 'long meal title' => [new MealTitleTooLong(80), 'A meal title has at most 80 characters.', 'Un titre de repas compte au plus 80 caractères.'];
    }
}
