<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\View;

class Goods extends BaseController
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
                  ->whereOr('barcode', 'like', "%{$keyword}%")
                  ->whereOr('cate', 'like', "%{$keyword}%");
            });
        }
        if ($cate !== '') {
            $query->where('cate', $cate);
        }
        $list = $query->order('id desc')->select()->toArray();

        $cates = Db::name('goods_cate')->order('id asc')->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'    => $this->getMenus(),
            'list'     => $list,
            'keyword'  => $keyword,
            'cate'     => $cate,
            'cateList' => $cates,
        ]));
        return View::fetch();
    }

    public function add()
    {
        $data = $this->request->post();
        $barcode = $data['barcode'] ?? '';

        $exist = Db::name('goods')->where('barcode', $barcode)->find();
        if ($exist) {
            return $this->jsonError('该条码已存在');
        }

        Db::name('goods')->insert([
            'name'           => $data['name'] ?? '',
            'barcode'        => $barcode,
            'unit'           => $data['unit'] ?? '',
            'box_spec'       => intval($data['box_spec'] ?? 0),
            'purchase_price' => floatval($data['purchase_price'] ?? 0),
            'retail_price'   => floatval($data['retail_price'] ?? 0),
            'stock'          => intval($data['stock'] ?? 0),
            'stock_min'      => $data['stock_min'] !== '' ? intval($data['stock_min']) : null,
            'stock_max'      => $data['stock_max'] !== '' ? intval($data['stock_max']) : null,
            'cate'           => $data['cate'] ?? '',
            'create_time'    => time(),
        ]);
        return $this->jsonSuccess([], '新增成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $id   = intval($data['id'] ?? 0);
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }

        $goods = Db::name('goods')->where('id', $id)->find();
        if (!$goods) {
            return $this->jsonError('商品不存在');
        }

        $newBarcode = $data['barcode'] ?? $goods['barcode'];
        if ($newBarcode !== $goods['barcode']) {
            $linkedCount = Db::name('purchase_detail')->where('barcode', $goods['barcode'])->count()
                         + Db::name('order_detail')->where('barcode', $goods['barcode'])->count();
            if ($linkedCount > 0) {
                return $this->jsonError('该商品已关联进货/订单，不可修改条码');
            }
            $exist = Db::name('goods')->where('barcode', $newBarcode)->where('id', '<>', $id)->find();
            if ($exist) {
                return $this->jsonError('该条码已被其他商品使用');
            }
        }

        Db::name('goods')->where('id', $id)->update([
            'name'           => $data['name'] ?? '',
            'barcode'        => $newBarcode,
            'unit'           => $data['unit'] ?? '',
            'box_spec'       => intval($data['box_spec'] ?? 0),
            'purchase_price' => floatval($data['purchase_price'] ?? 0),
            'retail_price'   => floatval($data['retail_price'] ?? 0),
            'cate'           => $data['cate'] ?? '',
            'stock_min'      => $data['stock_min'] !== '' ? intval($data['stock_min']) : null,
            'stock_max'      => $data['stock_max'] !== '' ? intval($data['stock_max']) : null,
        ]);
        return $this->jsonSuccess([], '编辑成功');
    }

    public function delete()
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }

        $goods = Db::name('goods')->where('id', $id)->find();
        if (!$goods) {
            return $this->jsonError('商品不存在');
        }

        $linkedCount = Db::name('purchase_detail')->where('barcode', $goods['barcode'])->count()
                     + Db::name('order_detail')->where('barcode', $goods['barcode'])->count();
        if ($linkedCount > 0) {
            return $this->jsonError('该商品已关联进货/订单，禁止删除');
        }

        Db::name('goods')->where('id', $id)->delete();
        return $this->jsonSuccess([], '删除成功');
    }

    public function genBarcode()
    {
        do {
            $barcode = '';
            for ($i = 0; $i < 13; $i++) {
                $barcode .= rand(0, 9);
            }
            $exist = Db::name('goods')->where('barcode', $barcode)->find();
        } while ($exist);

        return $this->jsonSuccess(['barcode' => $barcode]);
    }

    public function checkBarcode()
    {
        $barcode = $this->request->post('barcode', '');
        $id      = intval($this->request->post('id', 0));
        $query = Db::name('goods')->where('barcode', $barcode);
        if ($id > 0) {
            $query->where('id', '<>', $id);
        }
        $exist = $query->find();
        return $this->jsonSuccess(['exist' => $exist ? true : false]);
    }

    public function cateList()
    {
        $cateList = Db::name('goods_cate')->order('id asc')->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'    => $this->getMenus(),
            'cateList' => $cateList,
        ]));
        return View::fetch();
    }

    public function cateAdd()
    {
        $name = $this->request->post('name', '');
        if (empty($name)) {
            return $this->jsonError('分类名称不能为空');
        }
        $exist = Db::name('goods_cate')->where('name', $name)->find();
        if ($exist) {
            return $this->jsonError('该分类已存在');
        }
        Db::name('goods_cate')->insert(['name' => $name, 'create_time' => time()]);
        return $this->jsonSuccess([], '新增成功');
    }

    public function cateDelete()
    {
        $name = $this->request->post('name', '');
        if (empty($name)) {
            return $this->jsonError('参数错误');
        }
        $count = Db::name('goods')->where('cate', $name)->count();
        if ($count > 0) {
            return $this->jsonError("该分类下有 {$count} 个商品，不可删除");
        }
        Db::name('goods_cate')->where('name', $name)->delete();
        return $this->jsonSuccess([], '删除成功');
    }

    public function import()
    {
        $file = $this->request->file('file');
        if (!$file) {
            return $this->jsonError('请选择文件');
        }

        $ext = strtolower($file->getOriginalExtension());
        if (!in_array($ext, ['xls', 'xlsx'])) {
            return $this->jsonError('仅支持 Excel 文件');
        }

        $savePath = runtime_path() . 'import_' . time() . '.' . $ext;
        $file->move(dirname($savePath), basename($savePath));

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst($ext) === 'Xls' ? 'Xls' : 'Xlsx');
        $spreadsheet = $reader->load($savePath);
        $rows = $spreadsheet->getActiveSheet()->toArray();

        unlink($savePath);
        array_shift($rows);

        $successCount = 0;
        $failList     = [];
        $existBarcodes = array_column(Db::name('goods')->field('barcode')->select()->toArray(), 'barcode');
        $importBarcodes = [];

        foreach ($rows as $i => $row) {
            $name   = trim($row[0] ?? '');
            $barcode = trim($row[1] ?? '');
            $unit    = trim($row[2] ?? '');
            $boxSpec       = intval($row[3] ?? 0);
            $purchasePrice = floatval($row[4] ?? 0);
            $retailPrice   = floatval($row[5] ?? 0);
            $stock         = intval($row[6] ?? 0);
            $cate          = trim($row[7] ?? '');
            $stockMin      = isset($row[8]) && $row[8] !== '' ? intval($row[8]) : null;
            $stockMax      = isset($row[9]) && $row[9] !== '' ? intval($row[9]) : null;

            if (empty($name) || empty($barcode)) {
                $failList[] = "第" . ($i + 2) . "行：商品名称和条码不能为空";
                continue;
            }
            if (in_array($barcode, $existBarcodes, true) || in_array($barcode, $importBarcodes, true)) {
                $failList[] = "第" . ($i + 2) . "行：条码 {$barcode} 重复";
                continue;
            }
            $importBarcodes[] = $barcode;

            Db::name('goods')->insert([
                'name'           => $name,
                'barcode'        => $barcode,
                'unit'           => $unit,
                'box_spec'       => $boxSpec,
                'purchase_price' => $purchasePrice,
                'retail_price'   => $retailPrice,
                'stock'          => $stock,
                'cate'           => $cate,
                'stock_min'      => $stockMin,
                'stock_max'      => $stockMax,
                'create_time'    => time(),
            ]);
            $successCount++;
        }

        return $this->jsonSuccess([
            'success' => $successCount,
            'fail'    => count($failList),
            'details' => $failList,
        ], "导入完成：成功 {$successCount} 条，失败 " . count($failList) . " 条");
    }

    public function downloadTemplate()
    {
        $headers = ['商品名称', '商品条码', '单位', '箱规', '进货价', '零售价', '库存数量', '商品分类', '最小库存', '最大库存'];
        $this->downloadExcel($headers, [], '商品导入模板');
    }

    public function export()
    {
        $list = Db::name('goods')->select()->toArray();
        $headers = ['ID', '名称', '条码', '单位', '箱规', '进货价', '零售价', '库存', '最小库存', '最大库存', '分类', '创建时间'];
        $data = [];
        foreach ($list as $row) {
            $data[] = [
                $row['id'], $row['name'], $row['barcode'], $row['unit'] ?? '',
                $row['box_spec'] ?? 0,
                $row['purchase_price'], $row['retail_price'], $row['stock'],
                $row['stock_min'], $row['stock_max'], $row['cate'],
                date('Y-m-d H:i:s', $row['create_time']),
            ];
        }
        return $this->downloadExcel($headers, $data, '商品列表');
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
                $item = [
                    'title' => $rule['title'],
                    'icon'  => $rule['icon'] ?? '',
                    'url'   => !empty($rule['name']) ? url($rule['name'])->build() : '#',
                ];
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
