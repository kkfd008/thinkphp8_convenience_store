<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\View;

class Stock extends BaseController
{
    protected $middleware = ['auth'];

    public function index()
    {
        $keyword = $this->request->get('keyword', '');
        $cate    = $this->request->get('cate', '');

        $query = Db::name('goods');
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->whereOr('barcode', 'like', "%{$keyword}%");
            });
        }
        if ($cate !== '') {
            $query->where('cate', $cate);
        }

        $list = $query->order('id desc')->select()->toArray();

        $cates = Db::name('goods')->field('cate')->where('cate', '<>', '')->group('cate')->select()->toArray();
        $cateList = array_column($cates, 'cate');

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'    => $this->getMenus(),
            'list'     => $list,
            'keyword'  => $keyword,
            'cate'     => $cate,
            'cateList' => $cateList,
        ]));
        return View::fetch();
    }

    public function updateThreshold()
    {
        $id       = intval($this->request->post('id', 0));
        $stockMin = $this->request->post('stock_min', '');
        $stockMax = $this->request->post('stock_max', '');

        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }

        Db::name('goods')->where('id', $id)->update([
            'stock_min' => $stockMin !== '' ? intval($stockMin) : null,
            'stock_max' => $stockMax !== '' ? intval($stockMax) : null,
        ]);
        return $this->jsonSuccess([], '更新成功');
    }

    public function warning()
    {
        $type = $this->request->get('type', 'all');

        $query = Db::name('goods')
            ->where(function ($q) use ($type) {
                if ($type === 'low') {
                    $q->where('stock_min', '>', 0)->where('stock', '<', Db::raw('stock_min'));
                } elseif ($type === 'high') {
                    $q->where('stock_max', '>', 0)->where('stock', '>', Db::raw('stock_max'));
                } else {
                    $q->where(function ($sub) {
                        $sub->where(function ($s) {
                            $s->where('stock_min', '>', 0)->where('stock', '<', Db::raw('stock_min'));
                        })->whereOr(function ($s) {
                            $s->where('stock_max', '>', 0)->where('stock', '>', Db::raw('stock_max'));
                        });
                    });
                }
            });

        $list = $query->order('id desc')->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus' => $this->getMenus(),
            'list'  => $list,
            'type'  => $type,
        ]));
        return View::fetch();
    }

    public function detail()
    {
        $barcode = $this->request->get('barcode', '');
        if (empty($barcode)) {
            return $this->jsonError('参数错误');
        }

        $goods = Db::name('goods')->where('barcode', $barcode)->find();
        if (!$goods) {
            return $this->jsonError('商品不存在');
        }

        $inflows = Db::name('purchase_detail')->alias('pd')
            ->leftJoin('purchase p', 'pd.purchase_id = p.id')
            ->field('pd.create_time, pd.barcode, pd.goods_name, "进货" as type, (pd.box_spec * pd.box_count + pd.piece_count) as qty_change, p.purchase_no as ref_no')
            ->where('pd.barcode', $barcode)
            ->select()->toArray();

        $outflows = Db::name('order_detail')->alias('od')
            ->leftJoin('order o', 'od.order_id = o.id')
            ->field('od.create_time, od.barcode, od.goods_name, "销售" as type, -od.quantity as qty_change, o.order_no as ref_no')
            ->where('od.barcode', $barcode)
            ->select()->toArray();

        $rows = array_merge($inflows, $outflows);
        usort($rows, function ($a, $b) {
            return $a['create_time'] - $b['create_time'];
        });

        $balance = 0;
        foreach ($rows as &$row) {
            $balance += intval($row['qty_change']);
            $row['balance'] = $balance;
        }
        unset($row);

        return $this->jsonSuccess([
            'goods_name' => $goods['name'],
            'barcode'    => $goods['barcode'],
            'stock'      => $goods['stock'],
            'rows'       => $rows,
        ]);
    }

    public function warningExport()
    {
        $type = $this->request->get('type', 'all');

        $query = Db::name('goods')
            ->where(function ($q) use ($type) {
                if ($type === 'low') {
                    $q->where('stock_min', '>', 0)->where('stock', '<', Db::raw('stock_min'));
                } elseif ($type === 'high') {
                    $q->where('stock_max', '>', 0)->where('stock', '>', Db::raw('stock_max'));
                } else {
                    $q->where(function ($sub) {
                        $sub->where(function ($s) {
                            $s->where('stock_min', '>', 0)->where('stock', '<', Db::raw('stock_min'));
                        })->whereOr(function ($s) {
                            $s->where('stock_max', '>', 0)->where('stock', '>', Db::raw('stock_max'));
                        });
                    });
                }
            });

        $list = $query->select()->toArray();
        $headers = ['条码', '商品名称', '最小库存', '当前库存', '箱规'];
        $data = [];
        foreach ($list as $row) {
            $data[] = [
                $row['barcode'],
                $row['name'],
                $row['stock_min'] ?? '-',
                $row['stock'],
                $row['box_spec'] ?? 0,
            ];
        }
        $title = $type === 'low' ? '低库存预警' : ($type === 'high' ? '高库存预警' : '库存预警');
        return $this->downloadExcel($headers, $data, $title);
    }

    public function export()
    {
        $list = Db::name('goods')->select()->toArray();
        $headers = ['ID', '名称', '条码', '库存', 'stock_min', 'stock_max', '分类'];
        $data = [];
        foreach ($list as $row) {
            $data[] = [
                $row['id'], $row['name'], $row['barcode'], $row['stock'],
                $row['stock_min'], $row['stock_max'], $row['cate'],
            ];
        }
        return $this->downloadExcel($headers, $data, '库存列表');
    }

    private function getMenus()
    {
        $admin = session('admin_user');
        $role = Db::name('role')->where('id', $admin['role_id'])->find();
        $rulesArr = $role && !empty($role['rules']) ? explode(',', $role['rules']) : [];
        $allRules = Db::name('auth_rule')->order('sort asc, id asc')->select()->toArray();
        return $this->buildMenuTree($allRules, 0, $rulesArr);
    }

    private function buildMenuTree($rules, $pid, $allowedRules)
    {
        $tree = [];
        foreach ($rules as $rule) {
            if ($rule['pid'] == $pid) {
                if (!in_array((string)$rule['id'], $allowedRules, true)) continue;
                $item = ['title' => $rule['title'], 'icon' => $rule['icon'] ?? '', 'url' => !empty($rule['name']) ? url($rule['name'])->build() : '#'];
                $children = $this->buildMenuTree($rules, $rule['id'], $allowedRules);
                if (!empty($children)) $item['children'] = $children;
                $tree[] = $item;
            }
        }
        return $tree;
    }

    private function downloadExcel($headers, $data, $filename)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($headers as $i => $h) {
            $sheet->setCellValue([$i + 1, 1], $h);
        }
        foreach ($data as $ri => $row) {
            foreach ($row as $ci => $val) {
                $sheet->setCellValue([$ci + 1, $ri + 2], $val);
            }
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
