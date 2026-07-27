<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\BarcodeService;
use PHPUnit\Framework\TestCase;

class BarcodeServiceTest extends TestCase
{
    private BarcodeService $service;

    protected function setUp(): void
    {
        $this->service = new BarcodeService();
    }

    public function testGenerateBarcodeReturns13Digits(): void
    {
        $barcode = $this->service->generate();
        $this->assertIsString($barcode);
        $this->assertSame(13, strlen($barcode));
        $this->assertMatchesRegularExpression('/^\d{13}$/', $barcode);
    }

    public function testGenerateBarcodeStartsWithNonZero(): void
    {
        $barcode = $this->service->generate();
        $this->assertNotSame('0', $barcode[0]);
    }

    public function testGenerateMultipleBarcodesAreUnique(): void
    {
        $barcodes = [];
        for ($i = 0; $i < 100; $i++) {
            $barcodes[] = $this->service->generate();
        }
        $this->assertCount(100, array_unique($barcodes));
    }

    public function testIsValidBarcodeReturnsTrueFor13Digits(): void
    {
        $this->assertTrue($this->service->isValid('6901234567890'));
    }

    public function testIsValidBarcodeReturnsFalseForShortString(): void
    {
        $this->assertFalse($this->service->isValid('123'));
    }

    public function testIsValidBarcodeReturnsFalseForNonNumeric(): void
    {
        $this->assertFalse($this->service->isValid('abc1234567890'));
    }

    public function testIsValidBarcodeReturnsFalseForEmptyString(): void
    {
        $this->assertFalse($this->service->isValid(''));
    }
}