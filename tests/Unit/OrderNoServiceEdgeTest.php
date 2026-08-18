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

    // ==================== generateOutboundNo ====================

    public function testGenerateOutboundNoWithSequence1(): void
    {
        $no = $this->service->generateOutboundNo('20260727', 1);
        $this->assertSame('CK20260727001', $no);
    }

    public function testGenerateOutboundNoWithSequence999(): void
    {
        $no = $this->service->generateOutboundNo('20260727', 999);
        $this->assertSame('CK20260727999', $no);
    }

    public function testGenerateOutboundNoWithSequence0ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->generateOutboundNo('20260727', 0);
    }

    public function testGenerateOutboundNoWithNegativeSequence(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->generateOutboundNo('20260727', -1);
    }

    public function testGenerateOutboundNoWithSequenceOver999(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->generateOutboundNo('20260727', 1000);
    }

    // ==================== getNextSequenceForPrefix ====================

    public function testGetNextSequenceForPrefixFromLastNo(): void
    {
        // PD20260727001 → 2
        $this->assertSame(2, $this->service->getNextSequenceForPrefix('PD20260727001', 'PD'));
        // PD20260727099 → 100
        $this->assertSame(100, $this->service->getNextSequenceForPrefix('PD20260727099', 'PD'));
    }

    public function testGetNextSequenceForPrefixWithNull(): void
    {
        $this->assertSame(1, $this->service->getNextSequenceForPrefix(null, 'PD'));
    }

    public function testGetNextSequenceForPrefixWithEmpty(): void
    {
        $this->assertSame(1, $this->service->getNextSequenceForPrefix('', 'PD'));
    }

    public function testGetNextSequenceForPrefixWithShortString(): void
    {
        // 太短的字符串无法解析，返回1
        $this->assertSame(1, $this->service->getNextSequenceForPrefix('PD', 'PD'));
    }

    public function testGetNextSequenceForPrefixWithDifferentPrefix(): void
    {
        // CK前缀
        $this->assertSame(2, $this->service->getNextSequenceForPrefix('CK20260727001', 'CK'));
    }
}