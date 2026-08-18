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

    public function isLowStock(int $stock, int $stockMin): bool
    {
        return $stockMin > 0 && $stock < $stockMin;
    }

    public function isHighStock(int $stock, int $stockMax): bool
    {
        return $stockMax > 0 && $stock > $stockMax;
    }

    public function isExpiring(int $expiryDate, int $daysThreshold): bool
    {
        return $expiryDate > 0 && $expiryDate <= time() + $daysThreshold * 86400;
    }

    public function calcStockValue(int $stock, float $purchasePrice): float
    {
        return $stock * $purchasePrice;
    }

    public function calcStockAdjustDiff(int $newStock, int $oldStock): int
    {
        return $newStock - $oldStock;
    }
}