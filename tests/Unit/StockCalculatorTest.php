<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\StockCalculator;
use PHPUnit\Framework\TestCase;

class StockCalculatorTest extends TestCase
{
    private StockCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new StockCalculator();
    }

    public function testCalculatePurchaseStockAddWithBoxAndPiece(): void
    {
        // box_spec=12, box_count=2, piece_count=5 => 12*2+5 = 29
        $this->assertSame(29, $this->calculator->calcPurchaseAdd(12, 2, 5));
    }

    public function testCalculatePurchaseStockAddWithBoxOnly(): void
    {
        // box_spec=24, box_count=3, piece_count=0 => 24*3+0 = 72
        $this->assertSame(72, $this->calculator->calcPurchaseAdd(24, 3, 0));
    }

    public function testCalculatePurchaseStockAddWithPieceOnly(): void
    {
        // box_spec=0, box_count=0, piece_count=10 => 0+10 = 10
        $this->assertSame(10, $this->calculator->calcPurchaseAdd(0, 0, 10));
    }

    public function testCalculatePurchaseStockAddWithZero(): void
    {
        $this->assertSame(0, $this->calculator->calcPurchaseAdd(0, 0, 0));
    }

    public function testCalculatePurchaseItemTotal(): void
    {
        // purchase_price=5.5, box_spec=12, box_count=2, piece_count=5
        // => 5.5 * (12*2 + 5) = 5.5 * 29 = 159.5
        $this->assertSame(159.5, $this->calculator->calcPurchaseItemTotal(5.5, 12, 2, 5));
    }

    public function testCalculatePurchaseItemTotalWithPieceOnly(): void
    {
        // purchase_price=10.0, box_spec=0, box_count=0, piece_count=3
        // => 10.0 * 3 = 30.0
        $this->assertSame(30.0, $this->calculator->calcPurchaseItemTotal(10.0, 0, 0, 3));
    }

    public function testCalculateCheckoutStockDeduct(): void
    {
        // stock=100, quantity=5 => 95
        $this->assertSame(95, $this->calculator->calcAfterDeduct(100, 5));
    }

    public function testCalculateCheckoutStockDeductExact(): void
    {
        // stock=10, quantity=10 => 0
        $this->assertSame(0, $this->calculator->calcAfterDeduct(10, 10));
    }

    public function testIsStockSufficient(): void
    {
        $this->assertTrue($this->calculator->isStockSufficient(100, 5));
        $this->assertTrue($this->calculator->isStockSufficient(10, 10));
        $this->assertFalse($this->calculator->isStockSufficient(5, 10));
        $this->assertFalse($this->calculator->isStockSufficient(0, 1));
    }
}