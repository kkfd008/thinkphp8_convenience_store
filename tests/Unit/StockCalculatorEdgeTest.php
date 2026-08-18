<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\StockCalculator;
use PHPUnit\Framework\TestCase;

class StockCalculatorEdgeTest extends TestCase
{
    private StockCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new StockCalculator();
    }

    // ==================== isLowStock ====================

    public function testIsLowStockWhenBelowMin(): void
    {
        // stock=5, stockMin=10 => true (低库存)
        $this->assertTrue($this->calculator->isLowStock(5, 10));
    }

    public function testIsLowStockWhenEqualToMin(): void
    {
        // stock=10, stockMin=10 => false (刚好等于下限不算低库存)
        $this->assertFalse($this->calculator->isLowStock(10, 10));
    }

    public function testIsLowStockWhenAboveMin(): void
    {
        $this->assertFalse($this->calculator->isLowStock(20, 10));
    }

    public function testIsLowStockWithZeroMin(): void
    {
        // stockMin=0 表示未设置下限，不触发预警
        $this->assertFalse($this->calculator->isLowStock(0, 0));
        $this->assertFalse($this->calculator->isLowStock(5, 0));
    }

    // ==================== isHighStock ====================

    public function testIsHighStockWhenAboveMax(): void
    {
        // stock=50, stockMax=30 => true (高库存)
        $this->assertTrue($this->calculator->isHighStock(50, 30));
    }

    public function testIsHighStockWhenEqualToMax(): void
    {
        // stock=30, stockMax=30 => false (刚好等于上限不算高库存)
        $this->assertFalse($this->calculator->isHighStock(30, 30));
    }

    public function testIsHighStockWhenBelowMax(): void
    {
        $this->assertFalse($this->calculator->isHighStock(10, 30));
    }

    public function testIsHighStockWithZeroMax(): void
    {
        // stockMax=0 表示未设置上限，不触发预警
        $this->assertFalse($this->calculator->isHighStock(100, 0));
    }

    // ==================== isExpiring ====================

    public function testIsExpiringWithin30Days(): void
    {
        // 10天后到期
        $expiryDate = time() + 10 * 86400;
        $this->assertTrue($this->calculator->isExpiring($expiryDate, 30));
    }

    public function testIsExpiringExact30Days(): void
    {
        // 正好30天后到期
        $expiryDate = time() + 30 * 86400;
        $this->assertTrue($this->calculator->isExpiring($expiryDate, 30));
    }

    public function testIsExpiringBeyond30Days(): void
    {
        // 60天后到期
        $expiryDate = time() + 60 * 86400;
        $this->assertFalse($this->calculator->isExpiring($expiryDate, 30));
    }

    public function testIsExpiringWithZeroExpiry(): void
    {
        // expiryDate=0 表示未设置到期日期
        $this->assertFalse($this->calculator->isExpiring(0, 30));
    }

    public function testIsExpiringAlreadyExpired(): void
    {
        // 已经过期
        $expiryDate = time() - 1 * 86400;
        $this->assertTrue($this->calculator->isExpiring($expiryDate, 30));
    }

    // ==================== calcStockValue ====================

    public function testCalcStockValue(): void
    {
        // stock=10, purchasePrice=5.5 => 55.0
        $this->assertSame(55.0, $this->calculator->calcStockValue(10, 5.5));
    }

    public function testCalcStockValueWithZeroStock(): void
    {
        $this->assertSame(0.0, $this->calculator->calcStockValue(0, 5.5));
    }

    public function testCalcStockValueWithZeroPrice(): void
    {
        $this->assertSame(0.0, $this->calculator->calcStockValue(10, 0.0));
    }

    // ==================== calcStockAdjustDiff ====================

    public function testCalcStockAdjustDiffPositive(): void
    {
        // newStock=20, oldStock=10 => +10
        $this->assertSame(10, $this->calculator->calcStockAdjustDiff(20, 10));
    }

    public function testCalcStockAdjustDiffNegative(): void
    {
        // newStock=5, oldStock=10 => -5
        $this->assertSame(-5, $this->calculator->calcStockAdjustDiff(5, 10));
    }

    public function testCalcStockAdjustDiffZero(): void
    {
        $this->assertSame(0, $this->calculator->calcStockAdjustDiff(10, 10));
    }
}