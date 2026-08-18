<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\OrderNoService;
use think\facade\Db;
use think\facade\View;

class Outbound extends BaseController
{
    protected $middleware = ['auth'];

    public function index()
    {
        $keyword    = $this->request->get('keyword', '');
        $type       = $this->request->get('type', '');
        $startDate  = $this->request->get('start_date', '');
        $endDate    = $this->request->get('end_date', '');

        $suppliers = Db::name('supplier')->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'      => $this->getMenus(),
            'keyword'    => $keyword,
            'type'       => $type,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'suppliers'  => $suppliers,
        ]));
        return View::fetch();
    }

    public function list()
    {
        $page       = intval($this->request->get('page', 1));
        $limit      = intval($this->request->get('limit', 20));
        $keyword    = $this->request->get('keyword', '');
        $type       = $this->request->get('type', '');
        $startDate  = $this->request->get('start_date', '');
        $endDate    = $this->request->get('end_date', '');

        $query = Db::name('outbound')->alias('o')
            ->leftJoin('supplier s', 'o.supplier_id = s.id')
            ->field('o.*, s.name as supplier_name');

        if ($keyword !== '') {
            $query->where('o.outbound_no', 'like', "%{$keyword}%");
        }
        if ($type !== '') {
            $query->where('o.type', intval($type));
        }
        if ($startDate !== '') {
            $query->where('o.create_time', '>=', strtotime($startDate));
        }
        if ($endDate !== '') {
            $query->where('o.create_time', '<', strtotime($endDate) + 86400);
        }

        $count = $query->count();
        $list  = $query->order('o.id desc')->page($page, $limit)->select()->toArray();

        foreach ($list as &$row) {
            $row['type_text'] = $row['type'] == 1 ? '销售出库' : '退货出库';
        }

        return json(['code' => 0, 'msg' => '', 'count' => $count, 'data' => $list]);
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

        $type    = intval($data['type'] ?? 1);
        $itemsStr = $data['items'] ?? '[]';
        $items   = json_decode($itemsStr, true);

        if (!in_array($type, [1, 2])) {
            return $this->jsonError('请选择出库类型');
        }
        if ($type == 2) {
            $supplierId = intval($data['supplier_id'] ?? 0);
            if ($supplierId <= 0) {
                return $this->jsonError('退货出库请选择供货商');
            }
        }
        if (empty($items) || !is_array($items)) {
            return $this->jsonError('请添加出库明细');
        }

        $totalAmount   = 0;
        $totalGoodsNum = 0;

        foreach ($items as $i => &$item) {
            $quantity = intval($item['quantity'] ?? 0);

            if (empty($item['barcode'])) {
                return $this->jsonError("第" . ($i + 1) . "项条码不能为空");
            }
            if ($quantity <= 0) {
                return $this->jsonError("第" . ($i + 1) . "项数量必须大于0");
            }

            // 检查库存
            $goods = Db::name('goods')->where('barcode', $item['barcode'])->find();
            if (!$goods) {
                return $this->jsonError("第" . ($i + 1) . "项商品不存在");
            }
            if ($goods['stock'] < $quantity) {
                return $this->jsonError("第" . ($i + 1) . "项库存不足（当前库存：{$goods['stock']}）");
            }

            $itemTotal = floatval($item['purchase_price'] ?? 0) * $quantity;
            $item['total_amount'] = $itemTotal;
            $totalAmount   += $itemTotal;
            $totalGoodsNum += $quantity;
        }

        $admin  = session('admin_user');
        $date   = date('Ymd');
        $maxNo  = Db::name('outbound')
            ->where('outbound_no', 'like', "CK{$date}%")
            ->order('id desc')->value('outbound_no');

        $orderNoService = new OrderNoService();
        $seq = $orderNoService->getNextSequence($maxNo);
        $outboundNo = $orderNoService->generateOutboundNo($date, $seq);

        Db::startTrans();
        try {
            $outboundId = Db::name('outbound')->insertGetId([
                'outbound_no'     => $outboundNo,
                'type'            => $type,
                'supplier_id'     => $type == 2 ? intval($data['supplier_id'] ?? 0) : 0,
                'total_amount'    => $totalAmount,
                'total_goods_num' => $totalGoodsNum,
                'operator_id'     => $admin['id'],
                'remark'          => $data['remark'] ?? '',
                'create_time'     => time(),
            ]);

            foreach ($items as $item) {
                Db::name('outbound_detail')->insert([
                    'outbound_id'    => $outboundId,
                    'barcode'        => $item['barcode'],
                    'goods_name'     => $item['goods_name'],
                    'unit'           => $item['unit'] ?? '',
                    'quantity'       => intval($item['quantity']),
                    'purchase_price' => floatval($item['purchase_price'] ?? 0),
                    'retail_price'   => floatval($item['retail_price'] ?? 0),
                    'total_amount'   => $item['total_amount'],
                    'create_time'    => time(),
                ]);

                // 扣减库存
                Db::name('goods')->where('barcode', $item['barcode'])
                    ->dec('stock', intval($item['quantity']))
                    ->update();
            }

            Db::commit();
            return $this->jsonSuccess(['outbound_no' => $outboundNo], '出库单创建成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->jsonError('操作失败：' . $e->getMessage());
        }
    }

    public function detail()
    {
        $id = intval($this->request->get('id', 0));
        $outbound = Db::name('outbound')->alias('o')
            ->leftJoin('supplier s', 'o.supplier_id = s.id')
            ->field('o.*, s.name as supplier_name')
            ->where('o.id', $id)->find();

        if ($outbound) {
            $outbound['type_text'] = $outbound['type'] == 1 ? '销售出库' : '退货出库';
        }

        $details = Db::name('outbound_detail')->where('outbound_id', $id)->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'    => $this->getMenus(),
            'outbound' => $outbound,
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
            ->where('stock', '>', 0)
            ->limit(20)->select()->toArray();
        return $this->jsonSuccess($list);
    }

    public function export()
    {
        $keyword    = $this->request->get('keyword', '');
        $type       = $this->request->get('type', '');
        $startDate  = $this->request->get('start_date', '');
        $endDate    = $this->request->get('end_date', '');

        $query = Db::name('outbound')->alias('o')
            ->leftJoin('supplier s', 'o.supplier_id = s.id')
            ->field('o.*, s.name as supplier_name');

        if ($keyword !== '') {
            $query->where('o.outbound_no', 'like', "%{$keyword}%");
        }
        if ($type !== '') {
            $query->where('o.type', intval($type));
        }
        if ($startDate !== '') {
            $query->where('o.create_time', '>=', strtotime($startDate));
        }
        if ($endDate !== '') {
            $query->where('o.create_time', '<', strtotime($endDate) + 86400);
        }

        $list = $query->order('o.id desc')->select()->toArray();

        $headers = ['出库单号', '类型', '供货商', '总金额', '总数量', '备注', '时间'];
        $data = [];
        foreach ($list as $row) {
            $data[] = [
                $row['outbound_no'],
                $row['type'] == 1 ? '销售出库' : '退货出库',
                $row['supplier_name'] ?? '',
                $row['total_amount'],
                $row['total_goods_num'],
                $row['remark'],
                date('Y-m-d H:i:s', $row['create_time']),
            ];
        }
        return $this->downloadExcel($headers, $data, '出库单列表');
    }
}