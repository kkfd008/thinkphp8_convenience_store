<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\OrderImportService;
use PHPUnit\Framework\TestCase;

class OrderImportMultiGoodsTest extends TestCase
{
    private OrderImportService $service;

    protected function setUp(): void
    {
        $this->service = new OrderImportService();
    }

    public function testGroupByOrderNoWithMultipleGoodsPerOrder(): void
    {
        $rows = [
            ['order_no' => 'DD001', 'barcode' => '6900000000001', 'goods_name' => '可乐', 'quantity' => 2],
            ['order_no' => 'DD001', 'barcode' => '6900000000002', 'goods_name' => '薯片', 'quantity' => 1],
            ['order_no' => 'DD001', 'barcode' => '6900000000003', 'goods_name' => '口香糖', 'quantity' => 5],
            ['order_no' => 'DD002', 'barcode' => '6900000000004', 'goods_name' => '矿泉水', 'quantity' => 1],
        ];
        $grouped = $this->service->groupByOrderNo($rows);

        $this->assertCount(2, $grouped);
        $this->assertCount(3, $grouped['DD001'], '订单DD001应有3个商品');
        $this->assertCount(1, $grouped['DD002'], '订单DD002应有1个商品');
        $this->assertSame('可乐', $grouped['DD001'][0]['goods_name']);
        $this->assertSame('薯片', $grouped['DD001'][1]['goods_name']);
        $this->assertSame('口香糖', $grouped['DD001'][2]['goods_name']);
    }

    public function testGroupByOrderNoPreservesAllFields(): void
    {
        $rows = [
            ['order_no' => 'DD001', 'barcode' => 'A', 'goods_name' => '商品A', 'quantity' => 2, 'retail_price' => 5.0, 'total_amount' => 10.0],
            ['order_no' => 'DD001', 'barcode' => 'B', 'goods_name' => '商品B', 'quantity' => 1, 'retail_price' => 8.0, 'total_amount' => 8.0],
        ];
        $grouped = $this->service->groupByOrderNo($rows);

        $this->assertSame(5.0, $grouped['DD001'][0]['retail_price']);
        $this->assertSame(8.0, $grouped['DD001'][1]['retail_price']);
        $this->assertSame(10.0, $grouped['DD001'][0]['total_amount']);
        $this->assertSame(8.0, $grouped['DD001'][1]['total_amount']);
    }
}