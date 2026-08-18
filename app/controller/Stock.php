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

    // ==================== 库存总览 ====================

    public function index()
    {
        $keyword  = $this->request->get('keyword', '');
        $cate     = $this->request->get('cate', '');
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

    // ==================== 库存调整 ====================

    /**
     * 直接库存调整（修正/损耗/报损/其他）
     */
    public function adjust()
    {
        $barcode = $this->request->post('barcode', '');
        $newStock = intval($this->request->post('new_stock', 0));
        $reason   = $this->request->post('reason', '');
        $remark   = $this->request->post('remark', '');

        $goods = Db::name('goods')->where('barcode', $barcode)->find();
        if (!$goods) {
            return $this->jsonError('商品不存在');
        }

        $oldStock = intval($goods['stock']);
        $diff = $newStock - $oldStock;

        if ($diff === 0) {
            return $this->jsonError('库存未变化');
        }

        $admin = session('admin_user');

        Db::startTrans();
        try {
            Db::name('goods')->where('barcode', $barcode)->update(['stock' => $newStock]);

            $this->recordFlow($barcode, $goods['name'], $reason ?: '库存调整', $diff, $newStock, '', $admin['id'], $remark);

            Db::commit();
            return $this->jsonSuccess(['new_stock' => $newStock], '库存已从 ' . $oldStock . ' 调整为 ' . $newStock);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->jsonError('调整失败：' . $e->getMessage());
        }
    }

    // ==================== 库存阈值 ====================

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

    /**
     * 批量设置库存阈值
     */
    public function batchThreshold()
    {
        $idsStr   = $this->request->post('ids', '');
        $stockMin = $this->request->post('stock_min', '');
        $stockMax = $this->request->post('stock_max', '');

        $idArr = array_map('intval', explode(',', $idsStr));
        $idArr = array_filter($idArr, function ($v) { return $v > 0; });

        if (empty($idArr)) {
            return $this->jsonError('请选择商品');
        }

        $updateData = [];
        if ($stockMin !== '') $updateData['stock_min'] = intval($stockMin);
        if ($stockMax !== '') $updateData['stock_max'] = intval($stockMax);

        if (empty($updateData)) {
            return $this->jsonError('请至少设置一个阈值');
        }

        Db::name('goods')->whereIn('id', $idArr)->update($updateData);
        return $this->jsonSuccess([], '已更新 ' . count($idArr) . ' 个商品的阈值');
    }

    // ==================== 库存预警 ====================

    public function warning()
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

        $list = $query->order('g.id desc')->select()->toArray();

        $cates = Db::name('goods')->field('cate')->where('cate', '<>', '')->group('cate')->select()->toArray();
        $cateList = array_column($cates, 'cate');

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

    // ==================== 库存明细 / 流水 ====================

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

        // 优先从 stock_flow 表查询流水
        $flows = Db::name('stock_flow')
            ->where('barcode', $barcode)
            ->order('create_time asc')
            ->select()->toArray();

        // 如果流水表为空，回退到联合查询历史数据
        if (empty($flows)) {
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
        } else {
            $rows = $flows;
            foreach ($rows as &$row) {
                $row['ref_no'] = $row['ref_no'] ?: '-';
            }
            unset($row);
        }

        return $this->jsonSuccess([
            'goods_name'  => $goods['name'],
            'barcode'     => $goods['barcode'],
            'stock'       => $goods['stock'],
            'location'    => $goods['location'] ?? '',
            'expiry_date' => $goods['expiry_date'] ? date('Y-m-d', $goods['expiry_date']) : '',
            'rows'        => $rows,
        ]);
    }

    /**
     * 库存流水列表（全局）
     */
    public function flow()
    {
        $keyword = $this->request->get('keyword', '');
        $type    = $this->request->get('type', '');
        $dateBegin = $this->request->get('date_begin', '');
        $dateEnd   = $this->request->get('date_end', '');

        $cates = Db::name('goods')->field('cate')->where('cate', '<>', '')->group('cate')->select()->toArray();
        $cateList = array_column($cates, 'cate');

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'     => $this->getMenus(),
            'keyword'   => $keyword,
            'type'      => $type,
            'dateBegin' => $dateBegin,
            'dateEnd'   => $dateEnd,
            'cateList'  => $cateList,
        ]));
        return View::fetch();
    }

    public function flowList()
    {
        $page      = intval($this->request->get('page', 1));
        $limit     = intval($this->request->get('limit', 20));
        $keyword   = $this->request->get('keyword', '');
        $type      = $this->request->get('type', '');
        $dateBegin = $this->request->get('date_begin', '');
        $dateEnd   = $this->request->get('date_end', '');

        $query = Db::name('stock_flow');
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('barcode', 'like', "%{$keyword}%")
                  ->whereOr('goods_name', 'like', "%{$keyword}%");
            });
        }
        if ($type !== '') {
            $query->where('type', $type);
        }
        if ($dateBegin !== '') {
            $query->where('create_time', '>=', strtotime($dateBegin));
        }
        if ($dateEnd !== '') {
            $query->where('create_time', '<=', strtotime($dateEnd . ' 23:59:59'));
        }

        $count = $query->count();
        $list  = $query->order('id desc')->page($page, $limit)->select()->toArray();

        return json(['code' => 0, 'msg' => '', 'count' => $count, 'data' => $list]);
    }

    // ==================== 库存报表 ====================

    /**
     * 库存报表：库存金额、库龄、周转率
     */
    public function reports()
    {
        // 总库存金额
        $totalValue = Db::name('goods')->where('status', 1)->sum(Db::raw('stock * purchase_price'));

        // 总库存数量
        $totalStock = Db::name('goods')->where('status', 1)->sum('stock');

        // 商品种类数
        $totalGoods = Db::name('goods')->where('status', 1)->count();

        // 低库存商品数
        $lowStockCount = Db::name('goods')->where('status', 1)
            ->where('stock_min', '>', 0)->where('stock', '<', Db::raw('stock_min'))->count();

        // 临期商品数
        $expiryCount = Db::name('goods')->where('status', 1)
            ->where('expiry_date', '>', 0)->where('expiry_date', '<', time() + 30 * 86400)->count();

        // 按分类统计库存金额
        $cateStats = Db::query("
            SELECT cate, COUNT(*) as goods_count, SUM(stock) as total_stock,
                   SUM(stock * purchase_price) as total_value
            FROM goods WHERE status = 1 AND cate != ''
            GROUP BY cate ORDER BY total_value DESC
        ");

        // 按供货商统计库存金额
        $supplierStats = Db::query("
            SELECT s.name as supplier_name, COUNT(g.id) as goods_count,
                   SUM(g.stock) as total_stock, SUM(g.stock * g.purchase_price) as total_value
            FROM goods g LEFT JOIN supplier s ON g.supplier_id = s.id
            WHERE g.status = 1 AND g.supplier_id > 0
            GROUP BY g.supplier_id ORDER BY total_value DESC
        ");

        // 库龄分析（按创建时间分档）
        $agingStats = [
            'within_7d'  => Db::name('goods')->where('status', 1)->where('create_time', '>=', time() - 7 * 86400)->count(),
            'within_30d' => Db::name('goods')->where('status', 1)->where('create_time', '>=', time() - 30 * 86400)->where('create_time', '<', time() - 7 * 86400)->count(),
            'within_90d' => Db::name('goods')->where('status', 1)->where('create_time', '>=', time() - 90 * 86400)->where('create_time', '<', time() - 30 * 86400)->count(),
            'over_90d'   => Db::name('goods')->where('status', 1)->where('create_time', '<', time() - 90 * 86400)->count(),
        ];

        // 近30天入库量
        $recentInflow = Db::name('purchase_detail')->alias('pd')
            ->leftJoin('purchase p', 'pd.purchase_id = p.id')
            ->where('pd.create_time', '>=', time() - 30 * 86400)
            ->sum(Db::raw('pd.box_spec * pd.box_count + pd.piece_count'));

        // 近30天出库量
        $recentOutflow = Db::name('order_detail')
            ->where('create_time', '>=', time() - 30 * 86400)
            ->sum('quantity');

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'         => $this->getMenus(),
            'totalValue'    => $totalValue ?? 0,
            'totalStock'    => $totalStock ?? 0,
            'totalGoods'    => $totalGoods ?? 0,
            'lowStockCount' => $lowStockCount ?? 0,
            'expiryCount'   => $expiryCount ?? 0,
            'cateStats'     => $cateStats,
            'supplierStats' => $supplierStats,
            'agingStats'    => $agingStats,
            'recentInflow'  => $recentInflow ?? 0,
            'recentOutflow' => $recentOutflow ?? 0,
        ]));
        return View::fetch();
    }

    // ==================== 导出 ====================

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
                $row['barcode'], $row['name'], $row['cate'] ?? '-', $row['supplier_name'] ?? '-',
                $row['stock_min'] ?? '-', $row['stock_max'] ?? '-', $row['stock'],
                $row['expiry_date'] ? date('Y-m-d', $row['expiry_date']) : '-', $row['location'] ?? '-',
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

    // ==================== 私有方法 ====================

    /**
     * 记录库存流水
     */
    private function recordFlow(string $barcode, string $goodsName, string $type, int $qtyChange, int $balance, string $refNo, int $operatorId, string $remark): void
    {
        Db::name('stock_flow')->insert([
            'barcode'     => $barcode,
            'goods_name'  => $goodsName,
            'type'        => $type,
            'qty_change'  => $qtyChange,
            'balance'     => $balance,
            'ref_no'      => $refNo,
            'operator_id' => $operatorId,
            'remark'      => $remark,
            'create_time' => time(),
        ]);
    }
}