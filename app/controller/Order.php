<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\OrderDisplayService;
use app\service\OrderImportService;
use app\service\OrderNoService;
use app\service\DiscountCalculator;
use think\facade\Db;
use think\facade\View;

class Order extends BaseController
{
    protected $middleware = ['auth'];

    public function index()
    {
        $keyword   = $this->request->get('keyword', '');
        $memberId  = $this->request->get('member_id', '');
        $startDate = $this->request->get('start_date', '');
        $endDate   = $this->request->get('end_date', '');

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'      => $this->getMenus(),
            'keyword'    => $keyword,
            'member_id'  => $memberId,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]));
        return View::fetch();
    }

    public function list()
    {
        $page      = intval($this->request->get('page', 1));
        $limit     = intval($this->request->get('limit', 20));
        $keyword   = $this->request->get('keyword', '');
        $memberId  = $this->request->get('member_id', '');
        $startDate = $this->request->get('start_date', '');
        $endDate   = $this->request->get('end_date', '');

        $admin = session('admin_user');

        $query = Db::name('order')->alias('o')
            ->leftJoin('member m', 'o.member_id = m.id')
            ->leftJoin('admin_user a', 'o.operator_id = a.id')
            ->field('o.*, m.name as member_name, m.phone as member_phone, a.username as operator_name');

        if ($keyword !== '') {
            $query->where('o.order_no', 'like', "%{$keyword}%");
        }
        if ($memberId !== '') {
            $query->where('o.member_id', intval($memberId));
        }
        if ($startDate !== '') {
            $query->where('o.create_time', '>=', strtotime($startDate));
        }
        if ($endDate !== '') {
            $query->where('o.create_time', '<', strtotime($endDate) + 86400);
        }
        if ($admin['role_id'] == 3) {
            $query->where('o.operator_id', $admin['id']);
        }

        $count = $query->count();
        $rawList = $query->order('o.id desc')->page($page, $limit)->select()->toArray();

        $displayService = new OrderDisplayService();
        $list = $displayService->formatList($rawList);

        return json(['code' => 0, 'msg' => '', 'count' => $count, 'data' => $list]);
    }

    public function detail()
    {
        $id   = intval($this->request->get('id', 0));
        $order = Db::name('order')->alias('o')
            ->leftJoin('member m', 'o.member_id = m.id')
            ->field('o.*, m.name as member_name, m.phone as member_phone')
            ->where('o.id', $id)->find();

        $details = Db::name('order_detail')->alias('od')
            ->leftJoin('goods g', 'od.barcode = g.barcode')
            ->field('od.*, g.purchase_price')
            ->where('od.order_id', $id)->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'   => $this->getMenus(),
            'order'   => $order,
            'details' => $details,
        ]));
        return View::fetch();
    }

    public function export()
    {
        $list = Db::name('order')->alias('o')
            ->leftJoin('member m', 'o.member_id = m.id')
            ->leftJoin('admin_user a', 'o.operator_id = a.id')
            ->field('o.*, m.name as member_name, m.phone as member_phone, a.username as operator_name')
            ->select()->toArray();

        $displayService = new OrderDisplayService();
        $list = $displayService->formatList($list);

        $headers = ['订单号', '原价', '折扣', '实付', '支付方式', '购买者', '电话', '操作员', '时间'];
        $data = [];
        foreach ($list as $row) {
            $data[] = [
                $row['order_no'], $row['total_amount'], $row['discount_amount'],
                $row['pay_amount'], $row['pay_type_text'],
                $row['buyer_name'], $row['buyer_phone'],
                $row['operator_name'],
                date('Y-m-d H:i:s', $row['create_time']),
            ];
        }
        return $this->downloadExcel($headers, $data, '订单列表');
    }

    public function downloadTemplate()
    {
        $importService = new OrderImportService();
        $headers = $importService->getTemplateHeaders();
        $this->downloadExcel($headers, [], '订单导入模板');
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

        $savePath = runtime_path() . 'order_import_' . time() . '.' . $ext;
        $file->move(dirname($savePath), basename($savePath));

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst($ext) === 'Xls' ? 'Xls' : 'Xlsx');
        $spreadsheet = $reader->load($savePath);
        $rows = $spreadsheet->getActiveSheet()->toArray();

        unlink($savePath);
        array_shift($rows);

        $importService = new OrderImportService();
        $successOrders = 0;
        $successItems  = 0;
        $failList      = [];

        $orderRows = [];
        foreach ($rows as $i => $row) {
            $rowData = [
                'order_no'       => trim($row[0] ?? ''),
                'barcode'        => trim($row[1] ?? ''),
                'goods_name'     => trim($row[2] ?? ''),
                'quantity'       => intval($row[3] ?? 0),
                'retail_price'   => floatval($row[4] ?? 0),
                'purchase_price' => floatval($row[5] ?? 0),
                'total_amount'   => floatval($row[6] ?? 0),
                'discount_amount' => floatval($row[7] ?? 0),
                'pay_amount'     => floatval($row[8] ?? 0),
                'pay_type'       => trim($row[9] ?? ''),
                'member_name'    => trim($row[10] ?? ''),
                'member_phone'   => trim($row[11] ?? ''),
                'create_time'    => trim($row[12] ?? ''),
            ];

            $validateResult = $importService->validateRow($rowData);
            if (!$validateResult['valid']) {
                $failList[] = "第" . ($i + 2) . "行：" . $validateResult['error'];
                continue;
            }
            $orderRows[] = $rowData;
        }

        $grouped = $importService->groupByOrderNo($orderRows);
        $admin = session('admin_user');

        foreach ($grouped as $orderNo => $items) {
            $firstItem = $items[0];
            $payType = $importService->normalizePayType($firstItem['pay_type']);
            $totalAmount = 0;
            $discountAmount = 0;
            $payAmount = 0;

            foreach ($items as $item) {
                $totalAmount += $item['total_amount'];
                $discountAmount += $item['discount_amount'];
                $payAmount += $item['pay_amount'];
            }

            $memberId = 0;
            if (!empty($firstItem['member_phone'])) {
                $member = Db::name('member')->where('phone', $firstItem['member_phone'])->find();
                $memberId = $member ? $member['id'] : 0;
            }

            $createTime = !empty($firstItem['create_time']) ? strtotime($firstItem['create_time']) : time();

            Db::startTrans();
            try {
                $orderId = Db::name('order')->insertGetId([
                    'order_no'        => $orderNo,
                    'total_amount'    => $totalAmount,
                    'discount_amount' => $discountAmount,
                    'pay_amount'      => $payAmount,
                    'pay_type'        => $payType,
                    'member_id'       => $memberId,
                    'operator_id'     => $admin['id'],
                    'create_time'     => $createTime,
                ]);

                foreach ($items as $item) {
                    $goods = Db::name('goods')->where('barcode', $item['barcode'])->find();
                    Db::name('order_detail')->insert([
                        'order_id'       => $orderId,
                        'barcode'        => $item['barcode'],
                        'goods_name'     => $item['goods_name'] ?: ($goods['name'] ?? ''),
                        'quantity'       => $item['quantity'],
                        'retail_price'   => $item['retail_price'],
                        'total_amount'   => $item['total_amount'],
                        'discount_amount' => $item['discount_amount'],
                        'pay_amount'     => $item['pay_amount'],
                        'create_time'    => $createTime,
                    ]);
                }

                Db::commit();
                $successOrders++;
                $successItems += count($items);
            } catch (\Exception $e) {
                Db::rollback();
                $failList[] = "订单 {$orderNo} 写入失败：" . $e->getMessage();
            }
        }

        return $this->jsonSuccess([
            'orders'  => $successOrders,
            'items'   => $successItems,
            'fail'    => count($failList),
            'details' => $failList,
        ], "导入完成：成功 {$successOrders} 单（{$successItems} 条明细），失败 " . count($failList) . " 条");
    }

}
