<?php
declare(strict_types=1);

namespace app\service;

class DiscountCalculator
{
    public function calcPayAmount(float $totalAmount, float $discountRate): float
    {
        return round($totalAmount * $discountRate, 2);
    }

    public function calcDiscountAmount(float $totalAmount, float $payAmount): float
    {
        return round($totalAmount - $payAmount, 2);
    }

    public function calcSubtotal(float $retailPrice, int $quantity): float
    {
        return $retailPrice * $quantity;
    }

    public function isBalanceSufficient(float $balance, float $payAmount): bool
    {
        return $balance >= $payAmount;
    }

    public function calcBalanceAfterDeduct(float $balance, float $payAmount): float
    {
        return $balance - $payAmount;
    }
}