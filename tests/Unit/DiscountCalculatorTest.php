<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\DiscountCalculator;
use PHPUnit\Framework\TestCase;

class DiscountCalculatorTest extends TestCase
{
    private DiscountCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new DiscountCalculator();
    }

    public function testCalculatePayAmountWith98PercentDiscount(): void
    {
        // total=100.00, discount=0.98 => 98.00
        $this->assertSame(98.0, $this->calculator->calcPayAmount(100.0, 0.98));
    }

    public function testCalculatePayAmountWith95PercentDiscount(): void
    {
        // total=200.00, discount=0.95 => 190.00
        $this->assertSame(190.0, $this->calculator->calcPayAmount(200.0, 0.95));
    }

    public function testCalculatePayAmountWithNoDiscount(): void
    {
        // total=150.00, discount=1.00 => 150.00
        $this->assertSame(150.0, $this->calculator->calcPayAmount(150.0, 1.0));
    }

    public function testCalculatePayAmountWith90PercentDiscount(): void
    {
        // total=99.99, discount=0.90 => 89.99 (rounded to 2 decimals)
        $this->assertSame(89.99, $this->calculator->calcPayAmount(99.99, 0.90));
    }

    public function testCalculateDiscountAmount(): void
    {
        // total=100.00, pay=98.00 => 2.00
        $this->assertSame(2.0, $this->calculator->calcDiscountAmount(100.0, 98.0));
    }

    public function testCalculateDiscountAmountWithNoDiscount(): void
    {
        $this->assertSame(0.0, $this->calculator->calcDiscountAmount(150.0, 150.0));
    }

    public function testCalculateSubtotal(): void
    {
        // retail_price=15.5, quantity=3 => 46.5
        $this->assertSame(46.5, $this->calculator->calcSubtotal(15.5, 3));
    }

    public function testIsBalanceSufficient(): void
    {
        $this->assertTrue($this->calculator->isBalanceSufficient(100.0, 50.0));
        $this->assertTrue($this->calculator->isBalanceSufficient(50.0, 50.0));
        $this->assertFalse($this->calculator->isBalanceSufficient(50.0, 100.0));
        $this->assertFalse($this->calculator->isBalanceSufficient(0.0, 0.01));
    }

    public function testCalcBalanceAfterDeduct(): void
    {
        $this->assertSame(50.0, $this->calculator->calcBalanceAfterDeduct(100.0, 50.0));
        $this->assertSame(0.0, $this->calculator->calcBalanceAfterDeduct(50.0, 50.0));
    }
}