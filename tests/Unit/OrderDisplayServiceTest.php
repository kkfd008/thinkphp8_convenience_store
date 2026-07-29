<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\OrderDisplayService;
use PHPUnit\Framework\TestCase;

class OrderDisplayServiceTest extends TestCase
{
    private OrderDisplayService $service;

    protected function setUp(): void
    {
        $this->service = new OrderDisplayService();
    }

    public function testFormatOrderWithBuyerInfo(): void
    {
        $order = [
            'id' => 1,
            'order_no' => 'DD20260727001',
            'total_amount' => 100.0,
            'discount_amount' => 2.0,
            'pay_amount' => 98.0,
            'pay_type' => 1,
            'member_name' => '张三',
            'member_phone' => '13800138000',
            'operator_id' => 1,
            'operator_name' => '管理员',
            'create_time' => 1750000000,
        ];

        $result = $this->service->format($order);

        $this->assertSame('张三', $result['buyer_name']);
        $this->assertSame('13800138000', $result['buyer_phone']);
        $this->assertSame('管理员', $result['operator_name']);
        $this->assertSame('现金', $result['pay_type_text']);
    }

    public function testFormatOrderWithoutMember(): void
    {
        $order = [
            'id' => 2,
            'order_no' => 'DD20260727002',
            'total_amount' => 50.0,
            'discount_amount' => 0.0,
            'pay_amount' => 50.0,
            'pay_type' => 2,
            'member_name' => null,
            'member_phone' => null,
            'operator_id' => 2,
            'operator_name' => '店长',
            'create_time' => 1750000000,
        ];

        $result = $this->service->format($order);

        $this->assertSame('-', $result['buyer_name']);
        $this->assertSame('-', $result['buyer_phone']);
        $this->assertSame('店长', $result['operator_name']);
        $this->assertSame('会员余额', $result['pay_type_text']);
    }

    public function testFormatOrderWithEmptyStrings(): void
    {
        $order = [
            'id' => 3,
            'order_no' => 'DD20260727003',
            'total_amount' => 30.0,
            'discount_amount' => 0.0,
            'pay_amount' => 30.0,
            'pay_type' => 1,
            'member_name' => '',
            'member_phone' => '',
            'operator_id' => 3,
            'operator_name' => '收银员',
            'create_time' => 1750000000,
        ];

        $result = $this->service->format($order);

        $this->assertSame('-', $result['buyer_name']);
        $this->assertSame('-', $result['buyer_phone']);
        $this->assertSame('收银员', $result['operator_name']);
    }

    public function testFormatList(): void
    {
        $orders = [
            [
                'id' => 1,
                'order_no' => 'DD001',
                'total_amount' => 100.0,
                'discount_amount' => 2.0,
                'pay_amount' => 98.0,
                'pay_type' => 1,
                'member_name' => '张三',
                'member_phone' => '13800138000',
                'operator_id' => 1,
                'operator_name' => '管理员',
                'create_time' => 1750000000,
            ],
            [
                'id' => 2,
                'order_no' => 'DD002',
                'total_amount' => 50.0,
                'discount_amount' => 0.0,
                'pay_amount' => 50.0,
                'pay_type' => 2,
                'member_name' => null,
                'member_phone' => null,
                'operator_id' => 2,
                'operator_name' => '店长',
                'create_time' => 1750000000,
            ],
        ];

        $result = $this->service->formatList($orders);

        $this->assertCount(2, $result);
        $this->assertSame('张三', $result[0]['buyer_name']);
        $this->assertSame('13800138000', $result[0]['buyer_phone']);
        $this->assertSame('-', $result[1]['buyer_name']);
        $this->assertSame('-', $result[1]['buyer_phone']);
    }
}