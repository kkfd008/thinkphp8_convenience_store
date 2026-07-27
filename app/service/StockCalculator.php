<?php
declare(strict_types=1);

namespace app\service;

class StockCalculator
{
    public function calcPurchaseAdd(int $boxSpec, int $boxCount, int $pieceCount): int
    {
        return $boxSpec * $boxCount + $pieceCount;
    }

    public function calcPurchaseItemTotal(float $purchasePrice, int $boxSpec, int $boxCount, int $pieceCount): float
    {
        return $purchasePrice * ($boxSpec * $boxCount + $pieceCount);
    }

    public function calcAfterDeduct(int $stock, int $quantity): int
    {
        return $stock - $quantity;
    }

    public function isStockSufficient(int $stock, int $quantity): bool
    {
        return $stock >= $quantity;
    }
}