<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\OrderImportService;
use PHPUnit\Framework\TestCase;

class OrderImportServiceTest extends TestCase
{
    private OrderImportService $service;

    protected function setUp(): void
    {
        $this->service = new OrderImportService();
    }

    public function testGetTemplateHeaders(): void
    {
        $headers = $this->service->getTemplateHeaders();
        $this->assertIsArray($headers);
        $this->assertNotEmpty($headers);
        $this->assertContains('订单号', $headers);
        $this->assertContains('商品条码', $headers);
        $this->assertContains('数量', $headers);
    }

    public function testValidateRowWithValidData(): void
    {
        $row = [
            'order_no' => 'DD20260727001',
            'barcode' => '6901234567890',
            'goods_name' => '测试商品',
            'quantity' => 2,
            'retail_price' => 10.5,
            'total_amount' => 21.0,
            'discount_amount' => 0.0,
            'pay_amount' => 21.0,
            'pay_type' => '现金',
            'member_phone' => '',
            'create_time' => '2026-07-27',
        ];
        $result = $this->service->validateRow($row);
        $this->assertTrue($result['valid']);
    }

    public function testValidateRowRejectsEmptyOrderNo(): void
    {
        $row = [
            'order_no' => '',
            'barcode' => '6901234567890',
            'goods_name' => '测试商品',
            'quantity' => 2,
            'retail_price' => 10.5,
            'total_amount' => 21.0,
            'discount_amount' => 0.0,
            'pay_amount' => 21.0,
            'pay_type' => '现金',
            'member_phone' => '',
            'create_time' => '2026-07-27',
        ];
        $result = $this->service->validateRow($row);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('订单号', $result['error']);
    }

    public function testValidateRowRejectsEmptyBarcode(): void
    {
        $row = [
            'order_no' => 'DD20260727001',
            'barcode' => '',
            'goods_name' => '测试商品',
            'quantity' => 2,
            'retail_price' => 10.5,
            'total_amount' => 21.0,
            'discount_amount' => 0.0,
            'pay_amount' => 21.0,
            'pay_type' => '现金',
            'member_phone' => '',
            'create_time' => '2026-07-27',
        ];
        $result = $this->service->validateRow($row);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('条码', $result['error']);
    }

    public function testValidateRowRejectsInvalidQuantity(): void
    {
        $row = [
            'order_no' => 'DD20260727001',
            'barcode' => '6901234567890',
            'goods_name' => '测试商品',
            'quantity' => 0,
            'retail_price' => 10.5,
            'total_amount' => 21.0,
            'discount_amount' => 0.0,
            'pay_amount' => 21.0,
            'pay_type' => '现金',
            'member_phone' => '',
            'create_time' => '2026-07-27',
        ];
        $result = $this->service->validateRow($row);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('数量', $result['error']);
    }

    public function testValidateRowRejectsNegativeQuantity(): void
    {
        $row = [
            'order_no' => 'DD20260727001',
            'barcode' => '6901234567890',
            'goods_name' => '测试商品',
            'quantity' => -1,
            'retail_price' => 10.5,
            'total_amount' => 21.0,
            'discount_amount' => 0.0,
            'pay_amount' => 21.0,
            'pay_type' => '现金',
            'member_phone' => '',
            'create_time' => '2026-07-27',
        ];
        $result = $this->service->validateRow($row);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('数量', $result['error']);
    }

    public function testNormalizePayType(): void
    {
        $this->assertSame(1, $this->service->normalizePayType('现金'));
        $this->assertSame(2, $this->service->normalizePayType('会员余额'));
        $this->assertSame(1, $this->service->normalizePayType('现金支付'));
        $this->assertSame(2, $this->service->normalizePayType('余额'));
        $this->assertSame(1, $this->service->normalizePayType(''));
    }

    public function testGroupRowsByOrderNo(): void
    {
        $rows = [
            ['order_no' => 'DD001', 'barcode' => 'A', 'quantity' => 1],
            ['order_no' => 'DD001', 'barcode' => 'B', 'quantity' => 2],
            ['order_no' => 'DD002', 'barcode' => 'C', 'quantity' => 3],
        ];
        $grouped = $this->service->groupByOrderNo($rows);
        $this->assertCount(2, $grouped);
        $this->assertCount(2, $grouped['DD001']);
        $this->assertCount(1, $grouped['DD002']);
    }
}