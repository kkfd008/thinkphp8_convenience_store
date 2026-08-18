<?php
declare(strict_types=1);

namespace tests\Unit;

use app\service\PinyinService;
use PHPUnit\Framework\TestCase;

class PinyinServiceTest extends TestCase
{
    private PinyinService $service;

    protected function setUp(): void
    {
        $this->service = new PinyinService();
    }

    public function testGeneratePinyinCodeForChineseName(): void
    {
        // 可口可乐 → kkl (每个字的首字母)
        $code = $this->service->generatePinyinCode('可口可乐');
        $this->assertIsString($code);
        $this->assertNotEmpty($code);
    }

    public function testGeneratePinyinCodeForSingleChar(): void
    {
        // 水 → s
        $code = $this->service->generatePinyinCode('水');
        $this->assertIsString($code);
        $this->assertSame(1, strlen($code));
    }

    public function testGeneratePinyinCodeWithEmptyString(): void
    {
        $this->assertSame('', $this->service->generatePinyinCode(''));
    }

    public function testGeneratePinyinCodeWithWhitespace(): void
    {
        // 纯空格应返回空字符串
        $this->assertSame('', $this->service->generatePinyinCode('   '));
    }

    public function testGeneratePinyinCodeReturnsLowercase(): void
    {
        $code = $this->service->generatePinyinCode('测试');
        $this->assertSame($code, strtolower($code));
    }

    public function testGeneratePinyinCodeWithMixedAscii(): void
    {
        // 含 ASCII 数字的混合输入
        $code = $this->service->generatePinyinCode('abc123');
        $this->assertIsString($code);
        $this->assertNotEmpty($code);
    }

    public function testGeneratePinyinCodeTrimsWhitespace(): void
    {
        // 前后空格应被去除
        $code1 = $this->service->generatePinyinCode(' 测试 ');
        $code2 = $this->service->generatePinyinCode('测试');
        $this->assertSame($code2, $code1);
    }
}