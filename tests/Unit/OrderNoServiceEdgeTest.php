<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\OrderNoService;
use PHPUnit\Framework\TestCase;

class OrderNoServiceEdgeTest extends TestCase
{
    private OrderNoService $service;

    protected function setUp(): void
    {
        $this->service = new OrderNoService();
    }

    public function testGeneratePurchaseNoWithSequence0ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->generatePurchaseNo('20260727', 0);
    }

    public function testGenerateOrderNoWithSequence0ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->generateOrderNo('20260727', 0);
    }

    public function testGeneratePurchaseNoWithNegativeSequence(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->generatePurchaseNo('20260727', -1);
    }

    public function testGenerateOrderNoWithNegativeSequence(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->generateOrderNo('20260727', -1);
    }

    public function testGeneratePurchaseNoWithSequenceOver999(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->generatePurchaseNo('20260727', 1000);
    }

    public function testGenerateOrderNoWithSequenceOver999(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->generateOrderNo('20260727', 1000);
    }
}