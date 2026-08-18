<?php
declare(strict_types=1);

namespace app\service;

class OrderNoService
{
    private const MIN_SEQUENCE = 1;
    private const MAX_SEQUENCE = 999;

    private function validateSequence(int $sequence): void
    {
        if ($sequence < self::MIN_SEQUENCE || $sequence > self::MAX_SEQUENCE) {
            throw new \InvalidArgumentException(
                "Sequence must be between " . self::MIN_SEQUENCE . " and " . self::MAX_SEQUENCE . ", got {$sequence}"
            );
        }
    }

    public function generatePurchaseNo(string $date, int $sequence): string
    {
        $this->validateSequence($sequence);
        return 'JH' . $date . str_pad((string)$sequence, 3, '0', STR_PAD_LEFT);
    }

    public function generateOrderNo(string $date, int $sequence): string
    {
        $this->validateSequence($sequence);
        return 'DD' . $date . str_pad((string)$sequence, 3, '0', STR_PAD_LEFT);
    }

    public function generateOutboundNo(string $date, int $sequence): string
    {
        $this->validateSequence($sequence);
        return 'CK' . $date . str_pad((string)$sequence, 3, '0', STR_PAD_LEFT);
    }

    public function getNextSequence(?string $lastNo): int
    {
        if (empty($lastNo)) {
            return 1;
        }
        return intval(substr($lastNo, -3)) + 1;
    }

    /**
     * 根据前缀获取下一个序列号
     */
    public function getNextSequenceForPrefix(?string $lastNo, string $prefix): int
    {
        if (empty($lastNo)) {
            return 1;
        }
        $len = strlen($prefix);
        if (strlen($lastNo) < $len + 10) {
            return 1;
        }
        return intval(substr($lastNo, $len + 8, 3)) + 1;
    }
}