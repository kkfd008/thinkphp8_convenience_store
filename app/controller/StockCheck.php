<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\OrderNoService;
use think\facade\Db;
use think\facade\View;

class StockCheck extends BaseController
{
    protected $middleware = ['auth'];

    /**
     * 盘点列表
     */
    public function index()
    {
        $status = $this->request->get('status', '');

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'  => $this->getMenus(),
            'status' => $status,
        ]));
        return View::fetch();
    }

    /**
     * 盘点列表数据
     */
    public function list()
    {
        $page   = intval($this->request->get('page', 1));
        $limit  = intval($this->request->get('limit', 20));
        $status = $this->request->get('status', '');
        $keyword = $this->request->get('keyword', '');

        $query = Db::name('stock_check')->alias('sc')
            ->leftJoin('admin_user au', 'sc.operator_id = au.id')
            ->leftJoin('admin_user au2', 'sc.auditor_id = au2.id')
            ->field('sc.*, au.username as operator_name, au2.username as auditor_name');

        if ($status !== '') {
            $query->where('sc.status', intval($status));
        }
        if ($keyword !== '') {
            $query->where('sc.check_no', 'like', "%{$keyword}%");
        }

        $count = $query->count();
        $list  = $query->order('sc.id desc')->page($page, $limit)->select()->toArray();

        foreach ($list as &$row) {
            $row['status_text'] = $this->getStatusText($row['status']);
            $row['create_time'] = $row['create_time'] ? date('Y-m-d H:i:s', $row['create_time']) : '-';
            $row['audit_time']  = $row['audit_time'] ? date('Y-m-d H:i:s', $row['audit_time']) : '-';
        }

        return json(['code' => 0, 'msg' => '', 'count' => $count, 'data' => $list]);
    }

    /**
     * 新建盘点
     */
    public function add()
    {
        $templateId = intval($this->request->get('template_id', 0));

        // 获取盘点模板列表
        $templates = Db::name('stock_check_template')->order('id desc')->select()->toArray();

        // 如果指定了模板，预加载商品列表
        $template = null;
        $preloadGoods = [];
        if ($templateId > 0) {
            $template = Db::name('stock_check_template')->where('id', $templateId)->find();
            if ($template) {
                $query = Db::name('goods');
                if (!empty($template['cate'])) {
                    $query->where('cate', $template['cate']);
                }
                if (!empty($template['supplier_id'])) {
                    $query->where('supplier_id', $template['supplier_id']);
                }
                if (!empty($template['location'])) {
                    $query->where('location', $template['location']);
                }
                $preloadGoods = $query->select()->toArray();
            }
        }

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'        => $this->getMenus(),
            'templates'    => $templates,
            'templateId'   => $templateId,
            'template'     => $template,
            'preloadGoods' => $preloadGoods,
        ]));
        return View::fetch();
    }

    /**
     * 执行新建盘点
     */
    public function doAdd()
    {
        $data = $this->request->post();
        $itemsStr = $data['items'] ?? '[]';
        $items = json_decode($itemsStr, true);

        if (empty($items) || !is_array($items)) {
            return $this->jsonError('请添加盘点明细');
        }

        $admin = session('admin_user');
        $totalGoodsNum = 0;
        $profitNum = 0;
        $lossNum = 0;
        $profitAmount = 0;
        $lossAmount = 0;

        // 生成盘点单号
        $date = date('Ymd');
        $maxNo = Db::name('stock_check')->where('check_no', 'like', "PD{$date}%")->order('id desc')->value('check_no');
        $orderNoService = new OrderNoService();
        $seq = $orderNoService->getNextSequenceForPrefix($maxNo, 'PD');
        $checkNo = 'PD' . $date . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);

        Db::startTrans();
        try {
            $checkId = Db::name('stock_check')->insertGetId([
                'check_no'        => $checkNo,
                'status'          => 0,
                'total_goods_num' => 0,
                'profit_num'      => 0,
                'loss_num'        => 0,
                'profit_amount'   => 0,
                'loss_amount'     => 0,
                'operator_id'     => $admin['id'],
                'remark'          => $data['remark'] ?? '',
                'create_time'     => time(),
            ]);

            foreach ($items as $item) {
                $barcode = $item['barcode'] ?? '';
                $goods   = Db::name('goods')->where('barcode', $barcode)->find();
                if (!$goods) {
                    Db::rollback();
                    return $this->jsonError("商品 {$barcode} 不存在");
                }

                $bookStock   = intval($goods['stock']);
                $actualStock = intval($item['actual_stock'] ?? 0);
                $diff        = $actualStock - $bookStock;
                $diffAmount  = $diff * floatval($goods['purchase_price'] ?? 0);

                $totalGoodsNum++;
                if ($diff > 0) {
                    $profitNum++;
                    $profitAmount += $diffAmount;
                } elseif ($diff < 0) {
                    $lossNum++;
                    $lossAmount += abs($diffAmount);
                }

                Db::name('stock_check_detail')->insert([
                    'check_id'       => $checkId,
                    'barcode'        => $barcode,
                    'goods_name'     => $goods['name'],
                    'unit'           => $goods['unit'] ?? '',
                    'book_stock'     => $bookStock,
                    'actual_stock'   => $actualStock,
                    'diff'           => $diff,
                    'purchase_price' => $goods['purchase_price'] ?? 0,
                    'diff_amount'    => $diffAmount,
                    'reason'         => $item['reason'] ?? '',
                    'remark'         => $item['remark'] ?? '',
                    'create_time'    => time(),
                ]);
            }

            Db::name('stock_check')->where('id', $checkId)->update([
                'total_goods_num' => $totalGoodsNum,
                'profit_num'      => $profitNum,
                'loss_num'        => $lossNum,
                'profit_amount'   => $profitAmount,
                'loss_amount'     => $lossAmount,
            ]);

            Db::commit();
            return $this->jsonSuccess(['check_no' => $checkNo], '盘点单创建成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->jsonError('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 盘点明细
     */
    public function detail()
    {
        $id = intval($this->request->get('id', 0));
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }

        $check = Db::name('stock_check')->alias('sc')
            ->leftJoin('admin_user au', 'sc.operator_id = au.id')
            ->leftJoin('admin_user au2', 'sc.auditor_id = au2.id')
            ->field('sc.*, au.username as operator_name, au2.username as auditor_name')
            ->where('sc.id', $id)
            ->find();

        if (!$check) {
            return $this->jsonError('盘点单不存在');
        }

        $details = Db::name('stock_check_detail')->where('check_id', $id)->select()->toArray();

        $check['status_text'] = $this->getStatusText($check['status']);
        $check['create_time'] = date('Y-m-d H:i:s', $check['create_time']);
        $check['audit_time']  = $check['audit_time'] ? date('Y-m-d H:i:s', $check['audit_time']) : '-';

        return $this->jsonSuccess([
            'check'   => $check,
            'details' => $details,
        ]);
    }

    /**
     * 审核盘点单
     */
    public function audit()
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }

        $check = Db::name('stock_check')->where('id', $id)->find();
        if (!$check) {
            return $this->jsonError('盘点单不存在');
        }
        if ($check['status'] != 0) {
            return $this->jsonError('该盘点单状态不可审核');
        }

        $admin = session('admin_user');

        Db::startTrans();
        try {
            // 更新库存
            $details = Db::name('stock_check_detail')->where('check_id', $id)->select()->toArray();
            foreach ($details as $detail) {
                if ($detail['diff'] != 0) {
                    Db::name('goods')->where('barcode', $detail['barcode'])->update([
                        'stock' => $detail['actual_stock'],
                    ]);
                }
            }

            Db::name('stock_check')->where('id', $id)->update([
                'status'      => 2,
                'auditor_id'  => $admin['id'],
                'audit_time'  => time(),
            ]);

            Db::commit();
            return $this->jsonSuccess([], '审核成功，库存已更新');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->jsonError('审核失败：' . $e->getMessage());
        }
    }

    /**
     * 作废盘点单
     */
    public function cancel()
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }

        $check = Db::name('stock_check')->where('id', $id)->find();
        if (!$check) {
            return $this->jsonError('盘点单不存在');
        }
        if ($check['status'] == 2) {
            return $this->jsonError('已审核的盘点单不可作废');
        }

        Db::name('stock_check')->where('id', $id)->update(['status' => 3]);
        return $this->jsonSuccess([], '已作废');
    }

    /**
     * 搜索商品（用于盘点录入）
     */
    public function searchGoods()
    {
        $keyword = $this->request->get('keyword', '');
        if ($keyword === '') {
            return json(['code' => 0, 'data' => []]);
        }

        $list = Db::name('goods')
            ->where('name', 'like', "%{$keyword}%")
            ->whereOr('barcode', 'like', "%{$keyword}%")
            ->whereOr('pinyin_code', 'like', "%{$keyword}%")
            ->limit(20)
            ->select()
            ->toArray();

        return json(['code' => 0, 'data' => $list]);
    }

    /**
     * 导出盘点单
     */
    public function export()
    {
        $id = intval($this->request->get('id', 0));
        if ($id <= 0) {
            $check = Db::name('stock_check')->order('id desc')->find();
        } else {
            $check = Db::name('stock_check')->where('id', $id)->find();
        }

        if (!$check) {
            return $this->jsonError('盘点单不存在');
        }

        $details = Db::name('stock_check_detail')->where('check_id', $check['id'])->select()->toArray();

        $headers = ['条码', '商品名称', '单位', '账面库存', '实盘库存', '差异', '差异原因', '进价', '差异金额', '备注'];
        $data = [];
        foreach ($details as $d) {
            $data[] = [
                $d['barcode'], $d['goods_name'], $d['unit'] ?? '-',
                $d['book_stock'], $d['actual_stock'], $d['diff'],
                $d['reason'] ?? '-',
                $d['purchase_price'], $d['diff_amount'],
                $d['remark'] ?? '-',
            ];
        }

        return $this->downloadExcel($headers, $data, "盘点单_{$check['check_no']}");
    }

    /**
     * 盘点模板管理
     */
    public function template()
    {
        $templates = Db::name('stock_check_template')->order('id desc')->select()->toArray();

        // 分类列表
        $cates = Db::name('goods')->field('cate')->where('cate', '<>', '')->group('cate')->select()->toArray();
        $cateList = array_column($cates, 'cate');

        // 供货商列表
        $suppliers = Db::name('supplier')->field('id, name')->where('status', 1)->select()->toArray();

        // 库位列表
        $locations = Db::name('goods')->field('location')->where('location', '<>', '')->group('location')->select()->toArray();
        $locationList = array_column($locations, 'location');

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'        => $this->getMenus(),
            'templates'    => $templates,
            'cateList'     => $cateList,
            'suppliers'    => $suppliers,
            'locationList' => $locationList,
        ]));
        return View::fetch();
    }

    /**
     * 保存盘点模板
     */
    public function saveTemplate()
    {
        $data = $this->request->post();
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            return $this->jsonError('模板名称不能为空');
        }

        Db::name('stock_check_template')->insert([
            'name'        => $name,
            'type'        => $data['type'] ?? 'full',
            'cate'        => $data['cate'] ?? '',
            'supplier_id' => intval($data['supplier_id'] ?? 0),
            'location'    => $data['location'] ?? '',
            'remark'      => $data['remark'] ?? '',
            'create_time' => time(),
        ]);
        return $this->jsonSuccess([], '模板保存成功');
    }

    /**
     * 删除盘点模板
     */
    public function deleteTemplate()
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }
        Db::name('stock_check_template')->where('id', $id)->delete();
        return $this->jsonSuccess([], '已删除');
    }

    /**
     * 盘点统计报表
     */
    public function statistics()
    {
        // 按月份统计盘点情况
        $monthlyStats = Db::query("
            SELECT strftime('%Y-%m', datetime(create_time, 'unixepoch')) as month,
                   COUNT(*) as check_count,
                   SUM(total_goods_num) as total_goods,
                   SUM(profit_num) as total_profit,
                   SUM(loss_num) as total_loss,
                   SUM(profit_amount) as total_profit_amount,
                   SUM(loss_amount) as total_loss_amount
            FROM stock_check
            WHERE status = 2
            GROUP BY strftime('%Y-%m', datetime(create_time, 'unixepoch'))
            ORDER BY month DESC
            LIMIT 12
        ");

        // 总体统计
        $overall = Db::query("
            SELECT COUNT(*) as total_checks,
                   SUM(total_goods_num) as total_goods,
                   SUM(profit_num) as total_profit,
                   SUM(loss_num) as total_loss,
                   SUM(profit_amount) as total_profit_amount,
                   SUM(loss_amount) as total_loss_amount
            FROM stock_check
            WHERE status = 2
        ");

        // 按差异原因统计
        $reasonStats = Db::query("
            SELECT reason, COUNT(*) as cnt, SUM(ABS(diff)) as total_diff, SUM(ABS(diff_amount)) as total_amount
            FROM stock_check_detail
            WHERE reason != '' AND diff != 0
            GROUP BY reason
            ORDER BY cnt DESC
        ");

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'        => $this->getMenus(),
            'monthlyStats' => $monthlyStats,
            'overall'      => $overall[0] ?? [],
            'reasonStats'  => $reasonStats,
        ]));
        return View::fetch();
    }

    private function getStatusText(int $status): string
    {
        return match ($status) {
            0 => '待审核',
            1 => '盘点中',
            2 => '已审核',
            3 => '已作废',
            default => '未知',
        };
    }
}