<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\OrderNoService;
use PHPUnit\Framework\TestCase;

class OrderNoServiceTest extends TestCase
{
    private OrderNoService $service;

    protected function setUp(): void
    {
        $this->service = new OrderNoService();
    }

    public function testGeneratePurchaseNoWithSequence1(): void
    {
        $date = '20260727';
        $no = $this->service->generatePurchaseNo($date, 1);
        $this->assertSame('JH20260727001', $no);
    }

    public function testGeneratePurchaseNoWithSequence999(): void
    {
        $date = '20260727';
        $no = $this->service->generatePurchaseNo($date, 999);
        $this->assertSame('JH20260727999', $no);
    }

    public function testGeneratePurchaseNoWithSequence10(): void
    {
        $date = '20260101';
        $no = $this->service->generatePurchaseNo($date, 10);
        $this->assertSame('JH20260101010', $no);
    }

    public function testGenerateOrderNoWithSequence1(): void
    {
        $date = '20260727';
        $no = $this->service->generateOrderNo($date, 1);
        $this->assertSame('DD20260727001', $no);
    }

    public function testGenerateOrderNoWithSequence999(): void
    {
        $date = '20260727';
        $no = $this->service->generateOrderNo($date, 999);
        $this->assertSame('DD20260727999', $no);
    }

    public function testGenerateOrderNoWithSequence10(): void
    {
        $date = '20260101';
        $no = $this->service->generateOrderNo($date, 10);
        $this->assertSame('DD20260101010', $no);
    }

    public function testGetNextSequenceFromLastNo(): void
    {
        $this->assertSame(2, $this->service->getNextSequence('JH20260727001'));
        $this->assertSame(100, $this->service->getNextSequence('JH20260727099'));
        $this->assertSame(1, $this->service->getNextSequence(null));
        $this->assertSame(1, $this->service->getNextSequence(''));
    }
}