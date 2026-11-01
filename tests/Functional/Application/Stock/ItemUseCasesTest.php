<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Stock;

use App\Application\Stock\AddItem\AddItem;
use App\Application\Stock\AddItem\AddItemHandler;
use App\Application\Stock\AdjustStock\AdjustStock;
use App\Application\Stock\AdjustStock\AdjustStockHandler;
use App\Application\Stock\EditItem\EditItem;
use App\Application\Stock\EditItem\EditItemHandler;
use App\Application\Stock\ItemQueries;
use App\Application\Stock\ItemView;
use App\Application\Stock\RemoveItem\RemoveItem;
use App\Application\Stock\RemoveItem\RemoveItemHandler;
use App\Application\Stock\SetStock\SetStock;
use App\Application\Stock\SetStock\SetStockHandler;
use App\Domain\Shared\Exception\NotFound;
use App\Domain\Stock\Exception\EmptyItemName;
use App\Domain\Stock\Exception\ItemNameTaken;
use App\Domain\Stock\Exception\QuantityOutOfRange;
use App\Domain\Stock\Location;
use App\Domain\Stock\Unit;
use App\Tests\Support\FreezesClock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

final class ItemUseCasesTest extends KernelTestCase
{
    use FreezesClock;

    protected function setUp(): void
    {
        self::freezeAt('2026-10-06 08:00 UTC');
    }

    public function testAddAnItem(): void
    {
        $leeks = $this->add('  Leeks ', Location::Fridge, Unit::Piece);

        self::assertSame(['Leeks', 'fridge', 'piece', false, 0, 12, true, true], [$leeks->name, $leeks->location, $leeks->unit, $leeks->manual, $leeks->stock, $leeks->lowAt, $leeks->runningLow, $leeks->out]);
        self::assertTrue(Ulid::isValid($leeks->id));
    }

    public function testAddKeepsAnExplicitThreshold(): void
    {
        self::assertSame(6, $this->add('Milk', Location::Fridge, Unit::Carton, lowAt: 6)->lowAt);
    }

    public function testAddSetsTheStockWhenGiven(): void
    {
        $milk = $this->add('Milk', Location::Fridge, Unit::Carton, stock: 21);

        self::assertSame([21, false, false], [$milk->stock, $milk->runningLow, $milk->out]);
    }

    public function testAStockOutOfRangeIsRefusedOnAdd(): void
    {
        $this->expectExceptionObject(new QuantityOutOfRange(999));

        $this->add('Milk', stock: 11989);
    }

    public function testAddIsStoredForGood(): void
    {
        $this->add('Leeks');
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        self::assertSame(['Leeks'], array_map(static fn (ItemView $view): string => $view->name, self::getContainer()->get(ItemQueries::class)->all()));
    }

    public function testNameIsRequired(): void
    {
        $this->expectExceptionObject(new EmptyItemName());

        $this->add('  ');
    }

    public function testNamesAreUniqueIgnoringCaseAndPadding(): void
    {
        $this->add('Olive oil');

        $this->expectExceptionObject(new ItemNameTaken('OLIVE OIL'));

        $this->add(' OLIVE OIL ');
    }

    public function testNamesAreUniqueIgnoringCaseOnAccentedLetters(): void
    {
        $this->add('Œufs');

        $this->expectExceptionObject(new ItemNameTaken('œufs'));

        $this->add('œufs');
    }

    public function testAThresholdOutOfRangeIsRefused(): void
    {
        $this->expectExceptionObject(new QuantityOutOfRange(999));

        $this->add('Milk', lowAt: 11989);
    }

    public function testEditDescribesTheItemAgain(): void
    {
        $oil = $this->add('Oil', Location::Pantry, Unit::Piece);

        $edited = $this->edit($oil, 'Olive oil', Location::Freezer, Unit::Bottle, true, 6);

        self::assertSame(['Olive oil', 'freezer', 'bottle', true, 6], [$edited->name, $edited->location, $edited->unit, $edited->manual, $edited->lowAt]);
    }

    public function testEditWithoutThresholdFallsBackOnTheUnit(): void
    {
        $oil = $this->add('Oil', Location::Pantry, Unit::Piece);

        self::assertSame(3, $this->edit($oil, 'Oil', Location::Pantry, Unit::Bottle)->lowAt);
    }

    public function testEditKeepsTheStock(): void
    {
        $oil = $this->add('Oil');
        $this->adjust($oil, 24);

        self::assertSame(24, $this->edit($oil, 'Olive oil')->stock);
    }

    public function testAnItemMayChangeCaseWithoutClashingWithItself(): void
    {
        $oil = $this->add('olive oil');

        self::assertSame('Olive oil', $this->edit($oil, 'Olive oil')->name);
    }

    public function testCannotRenameToAnotherItemsName(): void
    {
        $this->add('Milk');
        $oil = $this->add('Oil');

        $this->expectExceptionObject(new ItemNameTaken('milk'));

        $this->edit($oil, 'milk');
    }

    public function testEditOfAnUnknownItemIsNotFound(): void
    {
        $id = new Ulid();

        $this->expectExceptionObject(new NotFound('item', (string) $id));

        self::getContainer()->get(EditItemHandler::class)(new EditItem($id, 'Oil', Location::Pantry, Unit::Bottle, false));
    }

    public function testAdjustAddsAndSubtractsRelatively(): void
    {
        $eggs = $this->add('Eggs');

        self::assertSame(24, $this->adjust($eggs, 24)->stock);
        self::assertSame(17, $this->adjust($eggs, -7)->stock);
    }

    public function testAdjustStopsAtZero(): void
    {
        $eggs = $this->add('Eggs');
        $this->adjust($eggs, 6);

        $view = $this->adjust($eggs, -24);

        self::assertSame([0, true, true], [$view->stock, $view->runningLow, $view->out]);
    }

    public function testAdjustIsStoredForGood(): void
    {
        $eggs = $this->add('Eggs');
        $this->adjust($eggs, 24);
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        self::assertSame([24], array_map(static fn (ItemView $view): int => $view->stock, self::getContainer()->get(ItemQueries::class)->all()));
    }

    public function testAdjustOfAnUnknownItemIsNotFound(): void
    {
        $id = new Ulid();

        $this->expectExceptionObject(new NotFound('item', (string) $id));

        self::getContainer()->get(AdjustStockHandler::class)(new AdjustStock($id, 12));
    }

    public function testSetStockReplacesTheStock(): void
    {
        $milk = $this->add('Milk', Location::Fridge, Unit::Carton);
        $this->adjust($milk, 36);

        $view = self::getContainer()->get(SetStockHandler::class)(new SetStock(Ulid::fromString($milk->id), 9));

        self::assertSame([9, false, false], [$view->stock, $view->out, $view->runningLow]);
    }

    public function testSetStockFlagsWhatRunsLow(): void
    {
        $milk = $this->add('Milk', Location::Fridge, Unit::Carton);

        $view = self::getContainer()->get(SetStockHandler::class)(new SetStock(Ulid::fromString($milk->id), 3));

        self::assertSame([true, false], [$view->runningLow, $view->out]);
    }

    public function testSetStockRefusesWhatIsOutOfRange(): void
    {
        $milk = $this->add('Milk');

        $this->expectExceptionObject(new QuantityOutOfRange(999));

        self::getContainer()->get(SetStockHandler::class)(new SetStock(Ulid::fromString($milk->id), 11989));
    }

    public function testRemoveDeletesTheItem(): void
    {
        $oil = $this->add('Oil');
        $this->add('Milk');

        self::getContainer()->get(RemoveItemHandler::class)(new RemoveItem(Ulid::fromString($oil->id)));
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        self::assertSame(['Milk'], array_map(static fn (ItemView $view): string => $view->name, self::getContainer()->get(ItemQueries::class)->all()));
    }

    public function testRemoveOfAnUnknownItemIsNotFound(): void
    {
        $id = new Ulid();

        $this->expectExceptionObject(new NotFound('item', (string) $id));

        self::getContainer()->get(RemoveItemHandler::class)(new RemoveItem($id));
    }

    public function testTheQueryListsByNameIgnoringCase(): void
    {
        $this->add('zucchini');
        $this->add('Apples');
        $this->add('beans');

        self::assertSame(['Apples', 'beans', 'zucchini'], array_map(static fn (ItemView $view): string => $view->name, self::getContainer()->get(ItemQueries::class)->all()));
    }

    public function testTheQueryListsNothingForAnEmptyKitchen(): void
    {
        self::assertSame([], self::getContainer()->get(ItemQueries::class)->all());
    }

    private function add(string $name, Location $location = Location::Pantry, Unit $unit = Unit::Piece, bool $manual = false, ?int $lowAt = null, int $stock = 0): ItemView
    {
        return self::getContainer()->get(AddItemHandler::class)(new AddItem($name, $location, $unit, $manual, $lowAt, $stock));
    }

    private function edit(ItemView $item, string $name, Location $location = Location::Pantry, Unit $unit = Unit::Piece, bool $manual = false, ?int $lowAt = null): ItemView
    {
        return self::getContainer()->get(EditItemHandler::class)(new EditItem(Ulid::fromString($item->id), $name, $location, $unit, $manual, $lowAt));
    }

    private function adjust(ItemView $item, int $by): ItemView
    {
        return self::getContainer()->get(AdjustStockHandler::class)(new AdjustStock(Ulid::fromString($item->id), $by));
    }
}
