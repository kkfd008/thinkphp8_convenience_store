<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\OrderNoService;
use think\facade\Db;
use think\facade\View;

class Stock extends BaseController
{
    protected $middleware = ['auth'];

    public function index()
    {
        $keyword = $this->request->get('keyword', '');
        $cate    = $this->request->get('cate', '');
        $location = $this->request->get('location', '');

        $cates = Db::name('goods')->field('cate')->where('cate', '<>', '')->group('cate')->select()->toArray();
        $cateList = array_column($cates, 'cate');

        $locations = Db::name('goods')->field('location')->where('location', '<>', '')->group('location')->select()->toArray();
        $locationList = array_column($locations, 'location');

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'        => $this->getMenus(),
            'keyword'      => $keyword,
            'cate'         => $cate,
            'location'     => $location,
            'cateList'     => $cateList,
            'locationList' => $locationList,
        ]));
        return View::fetch();
    }

    public function list()
    {
        $page     = intval($this->request->get('page', 1));
        $limit    = intval($this->request->get('limit', 20));
        $keyword  = $this->request->get('keyword', '');
        $cate     = $this->request->get('cate', '');
        $location = $this->request->get('location', '');

        $query = Db::name('goods');
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->whereOr('barcode', 'like', "%{$keyword}%")
                  ->whereOr('pinyin_code', 'like', "%{$keyword}%");
            });
        }
        if ($cate !== '') {
            $query->where('cate', $cate);
        }
        if ($location !== '') {
            $query->where('location', $location);
        }

        $count = $query->count();
        $list  = $query->order('id desc')->page($page, $limit)->select()->toArray();

        return json(['code' => 0, 'msg' => '', 'count' => $count, 'data' => $list]);
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
        $type       = $this->request->get('type', 'all');
        $cate       = $this->request->get('cate', '');
        $supplierId = intval($this->request->get('supplier_id', 0));

        $query = Db::name('goods')->alias('g')
            ->leftJoin('supplier s', 'g.supplier_id = s.id')
            ->field('g.*, s.name as supplier_name');

        // 预警条件
        $query->where(function ($q) use ($type) {
            if ($type === 'low') {
                $q->where('g.stock_min', '>', 0)->where('g.stock', '<', Db::raw('g.stock_min'));
            } elseif ($type === 'high') {
                $q->where('g.stock_max', '>', 0)->where('g.stock', '>', Db::raw('g.stock_max'));
            } elseif ($type === 'expiry') {
                $q->where('g.expiry_date', '>', 0)->where('g.expiry_date', '<', time() + 30 * 86400);
            } else {
                $q->where(function ($sub) {
                    $sub->where(function ($s) {
                        $s->where('g.stock_min', '>', 0)->where('g.stock', '<', Db::raw('g.stock_min'));
                    })->whereOr(function ($s) {
                        $s->where('g.stock_max', '>', 0)->where('g.stock', '>', Db::raw('g.stock_max'));
                    })->whereOr(function ($s) {
                        $s->where('g.expiry_date', '>', 0)->where('g.expiry_date', '<', time() + 30 * 86400);
                    });
                });
            }
        });

        if ($cate !== '') {
            $query->where('g.cate', $cate);
        }
        if ($supplierId > 0) {
            $query->where('g.supplier_id', $supplierId);
        }

        $list = $query->order('g.id desc')->select()->toArray();

        // 分类列表
        $cates = Db::name('goods')->field('cate')->where('cate', '<>', '')->group('cate')->select()->toArray();
        $cateList = array_column($cates, 'cate');

        // 供货商列表
        $suppliers = Db::name('supplier')->field('id, name')->where('status', 1)->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'      => $this->getMenus(),
            'list'       => $list,
            'type'       => $type,
            'cate'       => $cate,
            'supplierId' => $supplierId,
            'cateList'   => $cateList,
            'suppliers'  => $suppliers,
        ]));
        return View::fetch();
    }

    /**
     * 从预警列表一键生成采购单
     */
    public function generatePurchase()
    {
        $ids    = $this->request->post('ids', '');
        $supplierId = intval($this->request->post('supplier_id', 0));

        if (empty($ids)) {
            return $this->jsonError('请选择商品');
        }
        if ($supplierId <= 0) {
            return $this->jsonError('请选择供货商');
        }

        $idArr = array_map('intval', explode(',', $ids));
        $goodsList = Db::name('goods')->whereIn('id', $idArr)->select()->toArray();

        if (empty($goodsList)) {
            return $this->jsonError('未找到商品');
        }

        $admin = session('admin_user');
        $totalAmount = 0;
        $totalGoodsNum = 0;

        Db::startTrans();
        try {
            // 生成采购单号
            $date = date('Ymd');
            $maxNo = Db::name('purchase')->where('purchase_no', 'like', "JH{$date}%")->order('id desc')->value('purchase_no');
            $orderNoService = new OrderNoService();
            $seq = $orderNoService->getNextSequence($maxNo);
            $purchaseNo = $orderNoService->generatePurchaseNo($date, $seq);

            $purchaseId = Db::name('purchase')->insertGetId([
                'purchase_no'     => $purchaseNo,
                'supplier_id'     => $supplierId,
                'total_amount'    => 0,
                'total_goods_num' => 0,
                'operator_id'     => $admin['id'],
                'remark'          => '预警自动生成',
                'create_time'     => time(),
            ]);

            foreach ($goodsList as $goods) {
                // 补货量 = 库存上限 - 当前库存，最小为1
                $needQty = max(1, intval($goods['stock_max'] ?? 0) - intval($goods['stock']));
                $boxSpec = intval($goods['box_spec'] ?? 0);
                if ($boxSpec > 0) {
                    $boxCount = intdiv($needQty, $boxSpec);
                    $pieceCount = $needQty % $boxSpec;
                } else {
                    $boxCount = 0;
                    $pieceCount = $needQty;
                }
                $itemTotal = floatval($goods['purchase_price'] ?? 0) * $needQty;
                $totalAmount += $itemTotal;
                $totalGoodsNum += $needQty;

                Db::name('purchase_detail')->insert([
                    'purchase_id'    => $purchaseId,
                    'barcode'        => $goods['barcode'],
                    'goods_name'     => $goods['name'],
                    'unit'           => $goods['unit'] ?? '',
                    'purchase_price' => $goods['purchase_price'] ?? 0,
                    'retail_price'   => $goods['retail_price'] ?? 0,
                    'box_spec'       => $boxSpec,
                    'box_count'      => $boxCount,
                    'piece_count'    => $pieceCount,
                    'total_amount'   => $itemTotal,
                    'create_time'    => time(),
                ]);
            }

            Db::name('purchase')->where('id', $purchaseId)->update([
                'total_amount'    => $totalAmount,
                'total_goods_num' => $totalGoodsNum,
            ]);

            Db::commit();
            return $this->jsonSuccess(['purchase_no' => $purchaseNo], "采购单 {$purchaseNo} 已生成");
        } catch (\Exception $e) {
            Db::rollback();
            return $this->jsonError('生成失败：' . $e->getMessage());
        }
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
            ->field('pd.create_time, pd.barcode, pd.goods_name, "入库" as type, (pd.box_spec * pd.box_count + pd.piece_count) as qty_change, p.purchase_no as ref_no')
            ->where('pd.barcode', $barcode)
            ->select()->toArray();

        $outboundFlows = Db::name('outbound_detail')->alias('od')
            ->leftJoin('outbound o', 'od.outbound_id = o.id')
            ->field('od.create_time, od.barcode, od.goods_name, CASE WHEN o.type=1 THEN "销售出库" ELSE "退货出库" END as type, -od.quantity as qty_change, o.outbound_no as ref_no')
            ->where('od.barcode', $barcode)
            ->select()->toArray();

        $outflows = Db::name('order_detail')->alias('od')
            ->leftJoin('order o', 'od.order_id = o.id')
            ->field('od.create_time, od.barcode, od.goods_name, "销售" as type, -od.quantity as qty_change, o.order_no as ref_no')
            ->where('od.barcode', $barcode)
            ->select()->toArray();

        // 盘点调整记录
        $checkFlows = Db::name('stock_check_detail')->alias('scd')
            ->leftJoin('stock_check sc', 'scd.check_id = sc.id')
            ->field('scd.create_time, scd.barcode, scd.goods_name, "盘点调整" as type, scd.diff as qty_change, sc.check_no as ref_no')
            ->where('scd.barcode', $barcode)
            ->where('sc.status', 2)
            ->where('scd.diff', '<>', 0)
            ->select()->toArray();

        $rows = array_merge($inflows, $outboundFlows, $outflows, $checkFlows);
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
            'goods_name'  => $goods['name'],
            'barcode'     => $goods['barcode'],
            'stock'       => $goods['stock'],
            'location'    => $goods['location'] ?? '',
            'expiry_date' => $goods['expiry_date'] ? date('Y-m-d', $goods['expiry_date']) : '',
            'rows'        => $rows,
        ]);
    }

    public function warningExport()
    {
        $type       = $this->request->get('type', 'all');
        $cate       = $this->request->get('cate', '');
        $supplierId = intval($this->request->get('supplier_id', 0));

        $query = Db::name('goods')->alias('g')
            ->leftJoin('supplier s', 'g.supplier_id = s.id')
            ->field('g.*, s.name as supplier_name');

        $query->where(function ($q) use ($type) {
            if ($type === 'low') {
                $q->where('g.stock_min', '>', 0)->where('g.stock', '<', Db::raw('g.stock_min'));
            } elseif ($type === 'high') {
                $q->where('g.stock_max', '>', 0)->where('g.stock', '>', Db::raw('g.stock_max'));
            } elseif ($type === 'expiry') {
                $q->where('g.expiry_date', '>', 0)->where('g.expiry_date', '<', time() + 30 * 86400);
            } else {
                $q->where(function ($sub) {
                    $sub->where(function ($s) {
                        $s->where('g.stock_min', '>', 0)->where('g.stock', '<', Db::raw('g.stock_min'));
                    })->whereOr(function ($s) {
                        $s->where('g.stock_max', '>', 0)->where('g.stock', '>', Db::raw('g.stock_max'));
                    })->whereOr(function ($s) {
                        $s->where('g.expiry_date', '>', 0)->where('g.expiry_date', '<', time() + 30 * 86400);
                    });
                });
            }
        });

        if ($cate !== '') {
            $query->where('g.cate', $cate);
        }
        if ($supplierId > 0) {
            $query->where('g.supplier_id', $supplierId);
        }

        $list = $query->select()->toArray();

        $headers = ['条码', '商品名称', '分类', '供货商', '最小库存', '最大库存', '当前库存', '到期日期', '库位'];
        $data = [];
        foreach ($list as $row) {
            $data[] = [
                $row['barcode'],
                $row['name'],
                $row['cate'] ?? '-',
                $row['supplier_name'] ?? '-',
                $row['stock_min'] ?? '-',
                $row['stock_max'] ?? '-',
                $row['stock'],
                $row['expiry_date'] ? date('Y-m-d', $row['expiry_date']) : '-',
                $row['location'] ?? '-',
            ];
        }
        $titleMap = ['low' => '低库存预警', 'high' => '高库存预警', 'expiry' => '临期预警', 'all' => '库存预警'];
        $title = $titleMap[$type] ?? '库存预警';
        return $this->downloadExcel($headers, $data, $title);
    }

    public function export()
    {
        $list = Db::name('goods')->select()->toArray();
        $headers = ['ID', '名称', '条码', '拼音码', '库存', '最小库存', '最大库存', '到期日期', '库位', '分类'];
        $data = [];
        foreach ($list as $row) {
            $data[] = [
                $row['id'], $row['name'], $row['barcode'], $row['pinyin_code'] ?? '',
                $row['stock'], $row['stock_min'], $row['stock_max'],
                $row['expiry_date'] ? date('Y-m-d', $row['expiry_date']) : '',
                $row['location'] ?? '', $row['cate'],
            ];
        }
        return $this->downloadExcel($headers, $data, '库存列表');
    }

}