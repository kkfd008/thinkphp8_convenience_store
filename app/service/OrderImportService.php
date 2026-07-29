<?php
declare(strict_types=1);

namespace app\service;

class OrderImportService
{
    public function getTemplateHeaders(): array
    {
        return [
            '订单号', '商品条码', '商品名称', '数量', '零售价',
            '原价', '折扣', '实付', '支付方式', '会员手机号', '时间',
        ];
    }

    public function validateRow(array $row): array
    {
        $orderNo = trim($row['order_no'] ?? '');
        $barcode = trim($row['barcode'] ?? '');
        $quantity = intval($row['quantity'] ?? 0);

        if (empty($orderNo)) {
            return ['valid' => false, 'error' => '订单号不能为空'];
        }
        if (empty($barcode)) {
            return ['valid' => false, 'error' => '商品条码不能为空'];
        }
        if ($quantity <= 0) {
            return ['valid' => false, 'error' => '数量必须大于0'];
        }

        return ['valid' => true, 'error' => ''];
    }

    public function normalizePayType(string $payType): int
    {
        $payType = trim($payType);
        if (str_contains($payType, '会员') || str_contains($payType, '余额')) {
            return 2;
        }
        return 1;
    }

    public function groupByOrderNo(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $orderNo = $row['order_no'];
            $grouped[$orderNo][] = $row;
        }
        return $grouped;
    }
}