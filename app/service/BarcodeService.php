<?php
declare(strict_types=1);

namespace app\service;

class BarcodeService
{
    public function generate(): string
    {
        $barcode = (string)rand(1, 9);
        for ($i = 1; $i < 13; $i++) {
            $barcode .= (string)rand(0, 9);
        }
        return $barcode;
    }

    public function isValid(string $barcode): bool
    {
        if (strlen($barcode) !== 13) {
            return false;
        }
        if (!ctype_digit($barcode)) {
            return false;
        }
        return true;
    }
}