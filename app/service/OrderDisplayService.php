<?php
declare(strict_types=1);

namespace app\service;

class OrderDisplayService
{
    public function format(array $order): array
    {
        $order['buyer_name'] = !empty($order['member_name']) ? $order['member_name'] : '-';
        $order['buyer_phone'] = !empty($order['member_phone']) ? $order['member_phone'] : '-';
        $order['operator_name'] = $order['operator_name'] ?? '-';
        $order['pay_type_text'] = ($order['pay_type'] ?? 1) == 2 ? '会员余额' : '现金';
        return $order;
    }

    public function formatList(array $orders): array
    {
        return array_map([$this, 'format'], $orders);
    }
}