<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;

class GoodsCateService
{
    /**
     * 获取所有启用的分类
     */
    public function getActiveCates(array $filters = []): array
    {
        $query = Db::name('goods_cate');
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', 1);
        }
        return $query->order('id asc')->select()->toArray();
    }

    /**
     * 格式化分类列表为前端选项（含选中状态）
     */
    public function getCateSelectOptions(array $cates, string $selectedCate = ''): array
    {
        $options = [];
        foreach ($cates as $cate) {
            $options[] = [
                'name'     => $cate['name'],
                'selected' => $cate['name'] === $selectedCate,
            ];
        }
        return $options;
    }
}