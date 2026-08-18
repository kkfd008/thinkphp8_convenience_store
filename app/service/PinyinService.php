<?php
declare(strict_types=1);

namespace app\service;

/**
 * 拼音码服务 - 自动生成商品拼音首字母编码
 */
class PinyinService
{
    /**
     * 生成拼音首字母编码
     * 示例：可口可乐 → kkl、农夫山泉 → nfsq
     */
    public function generatePinyinCode(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $code = '';
        $len = mb_strlen($name, 'UTF-8');

        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($name, $i, 1, 'UTF-8');
            $pinyin = $this->getFirstLetter($char);
            $code .= $pinyin;
        }

        return strtolower($code);
    }

    /**
     * 获取单个汉字的首字母
     */
    private function getFirstLetter(string $char): string
    {
        // ASCII 字符直接返回
        if (ord($char) < 128) {
            return $char;
        }

        // 将 UTF-8 转换为 GB2312 编码以使用拼音区间映射
        $gb = @iconv('UTF-8', 'GB2312//IGNORE', $char);
        if ($gb === false || $gb === '' || strlen($gb) < 2) {
            return $char;
        }

        $fchar = ord($gb[0]);
        $schar = ord($gb[1]);

        $code = ($fchar << 8) + $schar;

        // 拼音首字母区间映射 (GB2312)
        if ($code >= 0xB0A1 && $code <= 0xB0C4) return 'a';
        if ($code >= 0xB0C5 && $code <= 0xB2C0) return 'b';
        if ($code >= 0xB2C1 && $code <= 0xB4ED) return 'c';
        if ($code >= 0xB4EE && $code <= 0xB6E9) return 'd';
        if ($code >= 0xB6EA && $code <= 0xB7A1) return 'e';
        if ($code >= 0xB7A2 && $code <= 0xB8C0) return 'f';
        if ($code >= 0xB8C1 && $code <= 0xB9FD) return 'g';
        if ($code >= 0xB9FE && $code <= 0xBBF6) return 'h';
        if ($code >= 0xBBF7 && $code <= 0xBFA5) return 'j';
        if ($code >= 0xBFA6 && $code <= 0xC0AB) return 'k';
        if ($code >= 0xC0AC && $code <= 0xC2E7) return 'l';
        if ($code >= 0xC2E8 && $code <= 0xC4C2) return 'm';
        if ($code >= 0xC4C3 && $code <= 0xC5B5) return 'n';
        if ($code >= 0xC5B6 && $code <= 0xC5BD) return 'o';
        if ($code >= 0xC5BE && $code <= 0xC6D9) return 'p';
        if ($code >= 0xC6DA && $code <= 0xC8BA) return 'q';
        if ($code >= 0xC8BB && $code <= 0xC8F5) return 'r';
        if ($code >= 0xC8F6 && $code <= 0xCBF9) return 's';
        if ($code >= 0xCBFA && $code <= 0xCDD9) return 't';
        if ($code >= 0xCDDA && $code <= 0xCEF3) return 'w';
        if ($code >= 0xCEF4 && $code <= 0xD1B8) return 'x';
        if ($code >= 0xD1B9 && $code <= 0xD4D0) return 'y';
        if ($code >= 0xD4D1 && $code <= 0xD7F9) return 'z';

        // 超出 GB2312 范围，返回原字符
        return $char;
    }
}