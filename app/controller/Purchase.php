<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\View;

class Purchase extends BaseController
{
    protected $middleware = ['auth'];

    public function index()
    {
        $keyword    = $this->request->get('keyword', '');
        $supplierId = $this->request->get('supplier_id', '');
        $startDate  = $this->request->get('start_date', '');
        $endDate    = $this->request->get('end_date', '');

        $query = Db::name('purchase')->alias('p')
            ->leftJoin('supplier s', 'p.supplier_id = s.id')
            ->field('p.*, s.name as supplier_name');

        if ($keyword !== '') {
            $query->where('p.purchase_no', 'like', "%{$keyword}%");
        }
        if ($supplierId !== '') {
            $query->where('p.supplier_id', intval($supplierId));
        }
        if ($startDate !== '') {
            $query->where('p.create_time', '>=', strtotime($startDate));
        }
        if ($endDate !== '') {
            $query->where('p.create_time', '<', strtotime($endDate) + 86400);
        }

        $list = $query->order('p.id desc')->select()->toArray();

        $suppliers = Db::name('supplier')->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'       => $this->getMenus(),
            'list'        => $list,
            'keyword'     => $keyword,
            'supplier_id' => $supplierId,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'suppliers'   => $suppliers,
        ]));
        return View::fetch();
    }

    public function add()
    {
        $suppliers = Db::name('supplier')->where('status', 1)->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'     => $this->getMenus(),
            'suppliers' => $suppliers,
        ]));
        return View::fetch();
    }

    public function doAdd()
    {
        $data = $this->request->post();

        $supplierId = intval($data['supplier_id'] ?? 0);
        $items      = json_decode($data['items'] ?? '[]', true);

        if ($supplierId <= 0) {
            return $this->jsonError('请选择供货商');
        }
        if (empty($items)) {
            return $this->jsonError('请添加进货明细');
        }

        $admin  = session('admin_user');
        $date   = date('Ymd');
        $maxNo  = Db::name('purchase')
            ->where('purchase_no', 'like', "JH{$date}%")
            ->order('id desc')->value('purchase_no');

        if ($maxNo) {
            $seq = intval(substr($maxNo, -3)) + 1;
        } else {
            $seq = 1;
        }
        $purchaseNo = 'JH' . $date . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);

        $totalAmount  = 0;
        $totalGoodsNum = 0;

        foreach ($items as &$item) {
            $itemTotal = floatval($item['purchase_price']) * (intval($item['box_spec']) * intval($item['box_count']) + intval($item['piece_count']));
            $item['total_amount'] = $itemTotal;
            $totalAmount  += $itemTotal;
            $totalGoodsNum += intval($item['box_spec']) * intval($item['box_count']) + intval($item['piece_count']);
        }

        Db::startTrans();
        try {
            $purchaseId = Db::name('purchase')->insertGetId([
                'purchase_no'    => $purchaseNo,
                'supplier_id'    => $supplierId,
                'total_amount'   => $totalAmount,
                'total_goods_num'=> $totalGoodsNum,
                'operator_id'    => $admin['id'],
                'remark'         => $data['remark'] ?? '',
                'create_time'    => time(),
            ]);

            foreach ($items as $item) {
                Db::name('purchase_detail')->insert([
                    'purchase_id'    => $purchaseId,
                    'barcode'        => $item['barcode'],
                    'goods_name'     => $item['goods_name'],
                    'unit'           => $item['unit'] ?? '',
                    'purchase_price' => floatval($item['purchase_price']),
                    'retail_price'   => floatval($item['retail_price']),
                    'box_spec'       => intval($item['box_spec']),
                    'box_count'      => intval($item['box_count']),
                    'piece_count'    => intval($item['piece_count']),
                    'total_amount'   => $item['total_amount'],
                    'create_time'    => time(),
                ]);

                $stockAdd = intval($item['box_spec']) * intval($item['box_count']) + intval($item['piece_count']);
                $boxSpec = intval($item['box_spec']);
                Db::name('goods')->where('barcode', $item['barcode'])
                    ->inc('stock', $stockAdd)
                    ->update(['box_spec' => $boxSpec]);
            }

            Db::commit();
            return $this->jsonSuccess(['purchase_no' => $purchaseNo], '进货单创建成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->jsonError('操作失败：' . $e->getMessage());
        }
    }

    public function detail()
    {
        $id = intval($this->request->get('id', 0));
        $purchase = Db::name('purchase')->alias('p')
            ->leftJoin('supplier s', 'p.supplier_id = s.id')
            ->field('p.*, s.name as supplier_name')
            ->where('p.id', $id)->find();

        $details = Db::name('purchase_detail')->where('purchase_id', $id)->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'    => $this->getMenus(),
            'purchase' => $purchase,
            'details'  => $details,
        ]));
        return View::fetch();
    }

    public function searchGoods()
    {
        $keyword = $this->request->get('keyword', '');
        $list = Db::name('goods')
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->whereOr('barcode', 'like', "%{$keyword}%");
            })
            ->limit(20)->select()->toArray();
        return $this->jsonSuccess($list);
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

        $savePath = runtime_path() . 'import_purchase_' . time() . '.' . $ext;
        $file->move(dirname($savePath), basename($savePath));

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst($ext) === 'Xls' ? 'Xls' : 'Xlsx');
        $spreadsheet = $reader->load($savePath);
        $rows = $spreadsheet->getActiveSheet()->toArray();
        unlink($savePath);
        array_shift($rows);

        $admin     = session('admin_user');
        $suppliers = array_column(Db::name('supplier')->field('id, name')->select()->toArray(), 'id', 'name');
        $goodsMap  = [];
        $goodsList = Db::name('goods')->field('barcode, name, purchase_price, retail_price')->select()->toArray();
        foreach ($goodsList as $g) {
            $goodsMap[$g['barcode']] = $g;
        }

        $successCount = 0;
        $failList     = [];

        foreach ($rows as $i => $row) {
            $supplierName = trim($row[0] ?? '');
            $barcode      = trim($row[1] ?? '');
            $goodsName    = trim($row[2] ?? '');
            $unit         = trim($row[3] ?? '');
            $purchasePrice = floatval($row[4] ?? 0);
            $retailPrice   = floatval($row[5] ?? 0);
            $boxSpec       = intval($row[6] ?? 0);
            $boxCount      = intval($row[7] ?? 0);
            $pieceCount    = intval($row[8] ?? 0);

            if (empty($supplierName) || empty($barcode)) {
                $failList[] = "第" . ($i + 2) . "行：供货商和条码不能为空";
                continue;
            }
            if (!isset($suppliers[$supplierName])) {
                $failList[] = "第" . ($i + 2) . "行：供货商 {$supplierName} 不存在";
                continue;
            }
            if (!isset($goodsMap[$barcode])) {
                $failList[] = "第" . ($i + 2) . "行：条码 {$barcode} 不存在";
                continue;
            }

            $goods = $goodsMap[$barcode];
            $supplierId = $suppliers[$supplierName];
            $total = $purchasePrice * ($boxSpec * $boxCount + $pieceCount);

            $date = date('Ymd');
            $maxNo = Db::name('purchase')->where('purchase_no', 'like', "JH{$date}%")->order('id desc')->value('purchase_no');
            $seq = $maxNo ? intval(substr($maxNo, -3)) + 1 : 1;
            $purchaseNo = 'JH' . $date . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);

            Db::startTrans();
            try {
                $purchaseId = Db::name('purchase')->insertGetId([
                    'purchase_no'    => $purchaseNo,
                    'supplier_id'    => $supplierId,
                    'total_amount'   => $total,
                    'total_goods_num'=> $boxSpec * $boxCount + $pieceCount,
                    'operator_id'    => $admin['id'],
                    'create_time'    => time(),
                ]);

                Db::name('purchase_detail')->insert([
                    'purchase_id'    => $purchaseId,
                    'barcode'        => $barcode,
                    'goods_name'     => $goodsName ?: $goods['name'],
                    'unit'           => $unit,
                    'purchase_price' => $purchasePrice,
                    'retail_price'   => $retailPrice,
                    'box_spec'       => $boxSpec,
                    'box_count'      => $boxCount,
                    'piece_count'    => $pieceCount,
                    'total_amount'   => $total,
                    'create_time'    => time(),
                ]);

                Db::name('goods')->where('barcode', $barcode)
                    ->inc('stock', $boxSpec * $boxCount + $pieceCount)
                    ->update(['box_spec' => $boxSpec]);
                Db::commit();
                $successCount++;
            } catch (\Exception $e) {
                Db::rollback();
                $failList[] = "第" . ($i + 2) . "行：写入失败";
            }
        }

        return $this->jsonSuccess([
            'success' => $successCount,
            'fail'    => count($failList),
            'details' => $failList,
        ], "导入完成");
    }

    public function downloadTemplate()
    {
        $headers = ['供货商名称', '商品条码', '商品名称', '单位', '进货价', '零售价', '箱规', '箱数', '散件数量'];
        $this->downloadExcel($headers, [], '进货导入模板');
    }

    public function importSheets()
    {
        $file = $this->request->file('file');
        if (!$file) return $this->jsonError('请选择文件');

        $ext = strtolower($file->getOriginalExtension());
        if (!in_array($ext, ['xls', 'xlsx'])) return $this->jsonError('仅支持 Excel 文件');

        $savePath = runtime_path() . 'import_sheets_' . time() . '.' . $ext;
        $file->move(dirname($savePath), basename($savePath));

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($savePath);
        $sheetCount = $spreadsheet->getSheetCount();

        $admin        = session('admin_user');
        $goodsMap     = [];
        $goodsList    = Db::name('goods')->field('barcode, name, purchase_price, retail_price')->select()->toArray();
        foreach ($goodsList as $g) {
            $goodsMap[$g['barcode']] = $g;
        }

        $supplierName = '武汉海聘电子商务有限公司';
        $supplier = Db::name('supplier')->where('name', $supplierName)->find();
        if (!$supplier) {
            $supplierId = Db::name('supplier')->insertGetId([
                'name' => $supplierName, 'status' => 1, 'create_time' => time(),
            ]);
        } else {
            $supplierId = $supplier['id'];
        }

        $totalOrders = 0;
        $totalItems  = 0;
        $log = [];

        for ($s = 0; $s < $sheetCount; $s++) {
            $sheet = $spreadsheet->getSheet($s);
            $rows  = $sheet->toArray();
            if (count($rows) < 4) continue;

            $sheetName = $spreadsheet->getSheetNames()[$s];

            // Find header row (contains "货号") and date row (contains "日期：")
            $headerRow = -1;
            $purchaseDate = '';
            $colOffset = 0;

            for ($r = 0; $r < min(count($rows), 5); $r++) {
                $rowText = implode('', array_map(function($v){ return (string)$v; }, $rows[$r]));
                if (strpos($rowText, '日期') !== false && $purchaseDate === '') {
                    for ($ci = 0; $ci < count($rows[$r]) - 1; $ci++) {
                        $cv = trim((string)($rows[$r][$ci] ?? ''));
                        if (strpos($cv, '日期') !== false) {
                            $nextVal = trim((string)($rows[$r][$ci + 1] ?? ''));
                            if (preg_match('/(\d{4})年(\d{1,2})月(\d{1,2})日/u', $nextVal, $m)) {
                                $purchaseDate = sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
                            }
                        }
                    }
                }
                if (strpos($rowText, '货号') !== false && strpos($rowText, '商品名称') !== false) {
                    $headerRow = $r;
                    // Detect column offset: if col 0 is empty/null and col 1 has "行号", shift
                    if (empty($rows[$r][0]) && !empty($rows[$r][1]) && strpos((string)$rows[$r][1], '行号') !== false) {
                        $colOffset = 1;
                    }
                }
            }

            if ($headerRow < 0) {
                $log[] = "Sheet '{$sheetName}' 跳过（未找到表头）";
                continue;
            }

            $items = [];
            for ($r = $headerRow + 1; $r < count($rows); $r++) {
                $row = $rows[$r];
                $barcode = trim((string)($row[$colOffset + 1] ?? ''));
                $name    = trim((string)($row[$colOffset + 2] ?? ''));

                // Stop conditions
                if (empty($barcode)) break;
                if (strpos($name, '金额小计') !== false || strpos($name, '合计') !== false) break;
                if (strpos($barcode, '核准人') !== false) break;

                $boxSpec  = intval($row[$colOffset + 3] ?? 0);
                $boxCount = floatval($row[$colOffset + 4] ?? 0);
                $totalQty = intval($row[$colOffset + 5] ?? 0);
                $unit     = trim((string)($row[$colOffset + 6] ?? ''));
                $purchasePrice = floatval($row[$colOffset + 7] ?? 0);
                $retailPrice   = floatval($row[$colOffset + 9] ?? 0);

                if (empty($barcode) || $barcode === '货号') continue;

                // Auto-create goods if barcode doesn't exist
                if (!isset($goodsMap[$barcode])) {
                    Db::name('goods')->insert([
                        'name'           => $name,
                        'barcode'        => $barcode,
                        'unit'           => $unit,
                        'purchase_price' => $purchasePrice,
                        'retail_price'   => $retailPrice,
                        'stock'          => 0,
                        'cate'           => '',
                        'create_time'    => time(),
                    ]);
                    $goodsMap[$barcode] = ['name' => $name, 'purchase_price' => $purchasePrice, 'retail_price' => $retailPrice];
                }

                $boxCountInt = intval($boxCount);
                $pieceCount  = $totalQty > 0 ? $totalQty - $boxSpec * $boxCountInt : 0;
                if ($pieceCount < 0) $pieceCount = 0;

                $items[] = [
                    'barcode'        => $barcode,
                    'goods_name'     => $name,
                    'unit'           => $unit,
                    'purchase_price' => $purchasePrice,
                    'retail_price'   => $retailPrice,
                    'box_spec'       => $boxSpec,
                    'box_count'      => $boxCountInt,
                    'piece_count'    => $pieceCount,
                ];
            }

            if (empty($items)) {
                $log[] = "Sheet '{$sheetName}' 跳过（无有效商品行）";
                continue;
            }

            // Create purchase order
            $totalAmount   = 0;
            $totalGoodsNum = 0;
            foreach ($items as &$item) {
                $itemTotal = $item['purchase_price'] * ($item['box_spec'] * $item['box_count'] + $item['piece_count']);
                $item['total_amount'] = $itemTotal;
                $totalAmount   += $itemTotal;
                $totalGoodsNum += $item['box_spec'] * $item['box_count'] + $item['piece_count'];
            }
            unset($item);

            $date = $purchaseDate ?: date('Ymd');
            $datePrefix = date('Ymd', strtotime($date));
            $maxNo = Db::name('purchase')->where('purchase_no', 'like', "JH{$datePrefix}%")->order('id desc')->value('purchase_no');
            $seq = $maxNo ? intval(substr($maxNo, -3)) + 1 : 1;
            $purchaseNo = 'JH' . $datePrefix . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);

            Db::startTrans();
            try {
                $purchaseId = Db::name('purchase')->insertGetId([
                    'purchase_no'     => $purchaseNo,
                    'supplier_id'     => $supplierId,
                    'total_amount'    => $totalAmount,
                    'total_goods_num' => $totalGoodsNum,
                    'operator_id'     => $admin['id'],
                    'remark'          => $sheetName,
                    'create_time'     => strtotime($date) ?: time(),
                ]);

                foreach ($items as $item) {
                    Db::name('purchase_detail')->insert([
                        'purchase_id'    => $purchaseId,
                        'barcode'        => $item['barcode'],
                        'goods_name'     => $item['goods_name'],
                        'unit'           => $item['unit'],
                        'purchase_price' => $item['purchase_price'],
                        'retail_price'   => $item['retail_price'],
                        'box_spec'       => $item['box_spec'],
                        'box_count'      => $item['box_count'],
                        'piece_count'    => $item['piece_count'],
                        'total_amount'   => $item['total_amount'],
                        'create_time'    => strtotime($date) ?: time(),
                    ]);

                    $stockAdd = $item['box_spec'] * $item['box_count'] + $item['piece_count'];
                    Db::name('goods')->where('barcode', $item['barcode'])
                        ->inc('stock', $stockAdd)
                        ->update(['box_spec' => $item['box_spec']]);
                }

                Db::commit();
                $totalOrders++;
                $totalItems += count($items);
                $log[] = "✓ {$sheetName} → {$purchaseNo}（{$date}，" . count($items) . "种商品，¥{$totalAmount}）";
            } catch (\Exception $e) {
                Db::rollback();
                $log[] = "✗ {$sheetName} 失败：" . $e->getMessage();
            }
        }

        unlink($savePath);

        return $this->jsonSuccess([
            'orders' => $totalOrders,
            'items'  => $totalItems,
            'log'    => $log,
        ], "导入完成：{$totalOrders}张进货单，{$totalItems}种商品");
    }

    public function export()
    {
        $list = Db::name('purchase')->alias('p')
            ->leftJoin('supplier s', 'p.supplier_id = s.id')
            ->field('p.*, s.name as supplier_name')
            ->select()->toArray();

        $headers = ['进货单号', '供货商', '总金额', '总数量', '操作员ID', '备注', '时间'];
        $data = [];
        foreach ($list as $row) {
            $data[] = [
                $row['purchase_no'], $row['supplier_name'],
                $row['total_amount'], $row['total_goods_num'],
                $row['operator_id'], $row['remark'],
                date('Y-m-d H:i:s', $row['create_time']),
            ];
        }
        return $this->downloadExcel($headers, $data, '进货单列表');
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
