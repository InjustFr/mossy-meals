<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Stock;

use App\Domain\Stock\Exception\EmptyItemName;
use App\Domain\Stock\Exception\HouseholdVariant;
use App\Domain\Stock\Exception\InvalidMonth;
use App\Domain\Stock\Exception\ItemNameTooLong;
use App\Domain\Stock\Exception\NestedVariant;
use App\Domain\Stock\Exception\QuantityOutOfRange;
use App\Domain\Stock\Exception\VariantOfItself;
use App\Domain\Stock\Item;
use App\Domain\Stock\Kind;
use App\Domain\Stock\Location;
use App\Domain\Stock\Months;
use App\Domain\Stock\Quantity;
use App\Domain\Stock\Unit;
use PHPUnit\Framework\TestCase;

final class ItemTest extends TestCase
{
    public function testStartsEmptyWithTheUnitsDefaultThreshold(): void
    {
        $milk = self::item('  Milk ', Unit::Carton);

        self::assertSame('Milk', $milk->name());
        self::assertTrue($milk->stock()->isZero());
        self::assertSame(3, $milk->lowAt()->twelfths());
        self::assertTrue($milk->isOut());
    }

    public function testNameIsRequired(): void
    {
        $this->expectExceptionObject(new EmptyItemName());

        self::item('   ');
    }

    public function testNameHasAtMostSixtyCharacters(): void
    {
        self::assertSame(60, mb_strlen(self::item(str_repeat('é', 60))->name()));

        $this->expectExceptionObject(new ItemNameTooLong(60));

        self::item(str_repeat('é', 61));
    }

    public function testDescribeReplacesWhatTheItemIs(): void
    {
        $oil = self::item('Oil', Unit::Piece);

        $oil->describe(' Olive oil ', Kind::Ingredient, Location::Fridge, Unit::Bottle, true, Quantity::ofTwelfths(6));

        self::assertSame(['Olive oil', Location::Fridge, Unit::Bottle, true, 6], [$oil->name(), $oil->location(), $oil->unit(), $oil->isManual(), $oil->lowAt()->twelfths()]);
    }

    public function testDescribeWithoutThresholdFallsBackOnTheUnit(): void
    {
        $oil = self::item('Oil', Unit::Piece, lowAt: Quantity::ofTwelfths(36));

        $oil->describe('Oil', Kind::Ingredient, Location::Pantry, Unit::Bottle, false, null);

        self::assertSame(3, $oil->lowAt()->twelfths());
    }

    public function testDescribeValidatesTheName(): void
    {
        $this->expectExceptionObject(new EmptyItemName());

        self::item('Oil')->describe(' ', Kind::Ingredient, Location::Pantry, Unit::Piece, false, null);
    }

    public function testRestockAndConsumeMoveTheStock(): void
    {
        $flour = self::item('Flour', Unit::Bag);

        $flour->restock(Quantity::ofTwelfths(24));
        $flour->consume(Quantity::ofTwelfths(9));

        self::assertSame(15, $flour->stock()->twelfths());
    }

    public function testConsumingMoreThanTheStockEmptiesIt(): void
    {
        $flour = self::item('Flour', Unit::Bag);
        $flour->setStock(Quantity::ofTwelfths(6));

        $flour->consume(Quantity::ofTwelfths(30));

        self::assertTrue($flour->isOut());
    }

    public function testAdjustMovesTheStockBothWaysAndStopsAtZero(): void
    {
        $eggs = self::item('Eggs');

        $eggs->adjust(12);
        $eggs->adjust(-5);
        self::assertSame(7, $eggs->stock()->twelfths());

        $eggs->adjust(-100);
        self::assertSame(0, $eggs->stock()->twelfths());
    }

    public function testAdjustCannotPassTheCeiling(): void
    {
        $eggs = self::item('Eggs');
        $eggs->setStock(Quantity::ofTwelfths(11988));

        $this->expectExceptionObject(new QuantityOutOfRange(999));

        $eggs->adjust(1);
    }

    public function testRunningLowFromTheThresholdDownwards(): void
    {
        $milk = self::item('Milk', Unit::Carton, lowAt: Quantity::ofTwelfths(6));

        $milk->setStock(Quantity::ofTwelfths(7));
        self::assertFalse($milk->isRunningLow());

        $milk->setStock(Quantity::ofTwelfths(6));
        self::assertTrue($milk->isRunningLow());
        self::assertFalse($milk->isOut());

        $milk->setStock(Quantity::zero());
        self::assertTrue($milk->isRunningLow());
        self::assertTrue($milk->isOut());
    }

    public function testWithoutAlertItIsNeverRunningLow(): void
    {
        $sauce = self::item('Pesto', Unit::Jar);
        self::assertTrue($sauce->alertsWhenLow());

        $sauce->alertWhenLow(false);

        self::assertFalse($sauce->isRunningLow());
        self::assertTrue($sauce->isOut());
    }

    public function testIsBoughtAllYearUntilToldOtherwise(): void
    {
        $zucchini = self::item('Zucchini');
        self::assertSame(range(1, 12), $zucchini->months()->toList());

        $zucchini->buyIn(Months::of([6, 7, 8, 9]));

        self::assertTrue($zucchini->isBoughtIn(7));
        self::assertFalse($zucchini->isBoughtIn(1));
        self::assertSame([6, 7, 8, 9], $zucchini->months()->toList());
    }

    public function testNoMonthMeansNeverBought(): void
    {
        $sodas = self::item('Sodas');
        $sodas->buyIn(Months::of([]));

        self::assertSame([], $sodas->months()->toList());
        self::assertFalse($sodas->isBoughtIn(5));
    }

    public function testAMonthGoesFromOneToTwelve(): void
    {
        $this->expectExceptionObject(new InvalidMonth(13));

        Months::of([1, 13]);
    }

    public function testAVariantJoinsTheFamilyOfItsMainItem(): void
    {
        $zucchini = self::item('Zucchini');
        $frozen = self::item('Frozen zucchini', Unit::Bag);
        $grilled = self::item('Grilled zucchini', Unit::Jar);
        $frozen->makeVariantOf($zucchini);
        $grilled->makeVariantOf($zucchini);

        self::assertSame($zucchini, $frozen->variantOf());
        self::assertSame([$zucchini, $frozen, $grilled], $zucchini->family());
        self::assertSame([$frozen, $zucchini, $grilled], $frozen->family());
        $leek = self::item('Leek');
        self::assertSame([$leek], $leek->family());
    }

    public function testLeavingAFamily(): void
    {
        $zucchini = self::item('Zucchini');
        $frozen = self::item('Frozen zucchini', Unit::Bag);
        $frozen->makeVariantOf($zucchini);

        $frozen->makeVariantOf(null);

        self::assertNull($frozen->variantOf());
        self::assertSame([$zucchini], $zucchini->family());
    }

    public function testAnItemIsNotAVariantOfItself(): void
    {
        $zucchini = self::item('Zucchini');

        $this->expectExceptionObject(new VariantOfItself('Zucchini'));

        $zucchini->makeVariantOf($zucchini);
    }

    public function testVariantsGoOneLevelDeep(): void
    {
        $zucchini = self::item('Zucchini');
        $frozen = self::item('Frozen zucchini', Unit::Bag);
        $frozen->makeVariantOf($zucchini);

        try {
            self::item('Cheap frozen zucchini')->makeVariantOf($frozen);
            self::fail('A variant of a variant was accepted.');
        } catch (NestedVariant $exception) {
            self::assertEquals(new NestedVariant('Cheap frozen zucchini'), $exception);
        }

        $this->expectExceptionObject(new NestedVariant('Zucchini'));

        $zucchini->makeVariantOf(self::item('Courgette'));
    }

    public function testAHouseholdItemIsTrackedByHandAndBoughtAllYear(): void
    {
        $soap = Item::add('Soap', Kind::Household, Location::Home, Unit::Bottle, false, null, new \DateTimeImmutable());
        $soap->buyIn(Months::of([]));
        self::assertSame([true, true, range(1, 12)], [$soap->isHousehold(), $soap->isManual(), $soap->months()->toList()]);

        $rice = self::item('Rice');
        $rice->describe('Rice', Kind::Household, Location::Home, Unit::Bag, false, null);

        self::assertTrue($rice->isManual());
    }

    public function testAHouseholdItemLivesAtHomeAndAnIngredientNever(): void
    {
        $mint = Item::add('Mint', Kind::Ingredient, Location::Home, Unit::Piece, false, null, new \DateTimeImmutable());
        $sponges = Item::add('Sponges', Kind::Household, Location::Fridge, Unit::Pack, false, null, new \DateTimeImmutable());

        self::assertSame([Kind::Ingredient, false, false, Location::Pantry], [$mint->kind(), $mint->isHousehold(), $mint->isManual(), $mint->location()]);
        self::assertSame([Kind::Household, true, true, Location::Home], [$sponges->kind(), $sponges->isHousehold(), $sponges->isManual(), $sponges->location()]);

        $sponges->describe('Sponges', Kind::Ingredient, Location::Home, Unit::Pack, false, null);
        $mint->describe('Mint', Kind::Ingredient, Location::Fridge, Unit::Piece, false, null);

        self::assertSame([Location::Pantry, Location::Fridge], [$sponges->location(), $mint->location()]);
    }

    public function testAHouseholdItemHasNoVariants(): void
    {
        $soap = Item::add('Soap', Kind::Household, Location::Home, Unit::Bottle, false, null, new \DateTimeImmutable());
        $attempts = [
            'Soap refill' => static fn () => Item::add('Soap refill', Kind::Household, Location::Home, Unit::Bag, false, null, new \DateTimeImmutable())->makeVariantOf($soap),
            'Olive oil' => static fn () => self::item('Olive oil')->makeVariantOf($soap),
            'Soap' => static fn () => $soap->makeVariantOf(self::item('Olive oil')),
        ];

        foreach ($attempts as $name => $attempt) {
            try {
                $attempt();
                self::fail("$name joined a family with a household item.");
            } catch (HouseholdVariant $exception) {
                self::assertEquals(new HouseholdVariant($name), $exception);
            }
        }

        $zucchini = self::item('Zucchini');
        self::item('Frozen zucchini')->makeVariantOf($zucchini);
        $zucchini->describe('Zucchini', Kind::Household, Location::Home, Unit::Piece, false, null);
        $this->expectExceptionObject(new HouseholdVariant('Zucchini'));

        $zucchini->makeVariantOf(null);
    }

    private static function item(string $name = 'Eggs', Unit $unit = Unit::Piece, bool $manual = false, ?Quantity $lowAt = null): Item
    {
        return Item::add($name, Kind::Ingredient, Location::Pantry, $unit, $manual, $lowAt, new \DateTimeImmutable());
    }
}
