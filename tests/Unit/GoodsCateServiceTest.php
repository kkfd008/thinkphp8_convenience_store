<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\GoodsCateService;
use PHPUnit\Framework\TestCase;

class GoodsCateServiceTest extends TestCase
{
    private GoodsCateService $service;

    protected function setUp(): void
    {
        $this->service = new GoodsCateService();
    }

    public function testGetCateSelectOptionsReturnsFormattedOptions(): void
    {
        $cates = [
            ['id' => 1, 'name' => '饮料', 'status' => 1],
            ['id' => 2, 'name' => '零食', 'status' => 1],
        ];
        $options = $this->service->getCateSelectOptions($cates);

        $this->assertIsArray($options);
        $this->assertCount(2, $options);
        $this->assertSame('饮料', $options[0]['name']);
        $this->assertSame('零食', $options[1]['name']);
    }

    public function testGetCateSelectOptionsWithSelectedCate(): void
    {
        $cates = [
            ['id' => 1, 'name' => '饮料', 'status' => 1],
            ['id' => 2, 'name' => '零食', 'status' => 1],
        ];
        $options = $this->service->getCateSelectOptions($cates, '零食');

        $this->assertFalse($options[0]['selected']);
        $this->assertTrue($options[1]['selected']);
    }

    public function testGetCateSelectOptionsWithEmptyArray(): void
    {
        $options = $this->service->getCateSelectOptions([]);
        $this->assertIsArray($options);
        $this->assertEmpty($options);
    }

    public function testGetCateSelectOptionsNoMatchSelected(): void
    {
        $cates = [
            ['id' => 1, 'name' => '饮料', 'status' => 1],
        ];
        $options = $this->service->getCateSelectOptions($cates, '不存在的分类');

        $this->assertFalse($options[0]['selected']);
    }

    public function testGetCateSelectOptionsPreservesOriginalData(): void
    {
        $cates = [
            ['id' => 1, 'name' => '饮料', 'status' => 1],
            ['id' => 2, 'name' => '零食', 'status' => 1],
        ];
        $options = $this->service->getCateSelectOptions($cates);

        $this->assertSame('饮料', $options[0]['name']);
        $this->assertFalse($options[0]['selected']);
        $this->assertSame('零食', $options[1]['name']);
        $this->assertFalse($options[1]['selected']);
    }
}