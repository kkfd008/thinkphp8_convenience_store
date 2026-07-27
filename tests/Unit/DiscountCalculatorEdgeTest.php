<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\DiscountCalculator;
use PHPUnit\Framework\TestCase;

class DiscountCalculatorEdgeTest extends TestCase
{
    private DiscountCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new DiscountCalculator();
    }

    public function testCalcPayAmountWithZeroTotal(): void
    {
        $this->assertSame(0.0, $this->calculator->calcPayAmount(0.0, 0.98));
    }

    public function testCalcPayAmountWithVerySmallDiscount(): void
    {
        // 100.00 * 0.01 = 1.00
        $this->assertSame(1.0, $this->calculator->calcPayAmount(100.0, 0.01));
    }

    public function testCalcPayAmountRoundingUp(): void
    {
        // 33.33 * 0.95 = 31.6635 -> rounds to 31.66
        $this->assertSame(31.66, $this->calculator->calcPayAmount(33.33, 0.95));
    }

    public function testCalcPayAmountRoundingDown(): void
    {
        // 33.33 * 0.90 = 29.997 -> rounds to 30.00
        $this->assertSame(30.0, $this->calculator->calcPayAmount(33.33, 0.90));
    }

    public function testCalcDiscountAmountRounding(): void
    {
        // 33.33 - 31.66 = 1.67
        $this->assertSame(1.67, $this->calculator->calcDiscountAmount(33.33, 31.66));
    }

    public function testCalcSubtotalWithZeroQuantity(): void
    {
        $this->assertSame(0.0, $this->calculator->calcSubtotal(15.5, 0));
    }

    public function testCalcSubtotalWithLargeQuantity(): void
    {
        $this->assertSame(1550.0, $this->calculator->calcSubtotal(15.5, 100));
    }

    public function testCalcBalanceAfterDeductWithZero(): void
    {
        $this->assertSame(0.0, $this->calculator->calcBalanceAfterDeduct(0.0, 0.0));
    }
}