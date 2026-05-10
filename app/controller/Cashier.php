<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\View;

class Cashier extends BaseController
{
    protected $middleware = ['auth'];

    public function index()
    {
        View::assign(array_merge($this->assignAdminUser(), [
            'menus' => $this->getMenus(),
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

    public function searchMember()
    {
        $keyword = $this->request->get('keyword', '');
        $list = Db::name('member')->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
              ->whereOr('phone', 'like', "%{$keyword}%");
        })->limit(10)->select()->toArray();

        foreach ($list as &$m) {
            $cate = Db::name('member_cate')->where('id', $m['cate_id'])->find();
            $m['discount'] = $cate ? $cate['discount'] : 1.00;
        }

        return $this->jsonSuccess($list);
    }

    public function doCheckout()
    {
        $data     = $this->request->post();
        $items    = json_decode($data['items'] ?? '[]', true);
        $payType  = intval($data['pay_type'] ?? 1);
        $memberId = intval($data['member_id'] ?? 0);

        if (empty($items)) {
            return $this->jsonError('购物车为空');
        }

        $admin = session('admin_user');
        $totalAmount    = 0;
        $discountAmount = 0;

        $discountRate = 1.00;
        $memberBalance = 0;
        if ($memberId > 0) {
            $member = Db::name('member')->where('id', $memberId)->find();
            if (!$member) {
                return $this->jsonError('会员不存在');
            }
            $cate = Db::name('member_cate')->where('id', $member['cate_id'])->find();
            $discountRate = $cate ? floatval($cate['discount']) : 1.00;
            $memberBalance = floatval($member['balance']);
        }

        foreach ($items as $item) {
            $goods = Db::name('goods')->where('barcode', $item['barcode'])->find();
            if (!$goods) {
                return $this->jsonError("商品 {$item['barcode']} 不存在");
            }
            if ($goods['stock'] < intval($item['quantity'])) {
                return $this->jsonError("商品 {$goods['name']} 库存不足（库存:{$goods['stock']}, 需要:{$item['quantity']}）");
            }
            $subtotal = floatval($goods['retail_price']) * intval($item['quantity']);
            $totalAmount += $subtotal;
        }

        $payAmount = round($totalAmount * $discountRate, 2);
        $discountAmount = round($totalAmount - $payAmount, 2);

        if ($payType == 2 && $memberId > 0) {
            if ($memberBalance < $payAmount) {
                return $this->jsonError('会员余额不足');
            }
        }

        $date = date('Ymd');
        $maxNo = Db::name('order')->where('order_no', 'like', "DD{$date}%")->order('id desc')->value('order_no');
        $seq = $maxNo ? intval(substr($maxNo, -3)) + 1 : 1;
        $orderNo = 'DD' . $date . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);

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
                'create_time'     => time(),
            ]);

            foreach ($items as $item) {
                $goods = Db::name('goods')->where('barcode', $item['barcode'])->find();
                $quantity = intval($item['quantity']);
                $detailTotal = floatval($goods['retail_price']) * $quantity;

                Db::name('order_detail')->insert([
                    'order_id'     => $orderId,
                    'barcode'      => $item['barcode'],
                    'goods_name'   => $goods['name'],
                    'retail_price' => $goods['retail_price'],
                    'quantity'     => $quantity,
                    'total_amount' => $detailTotal,
                    'create_time'  => time(),
                ]);

                Db::name('goods')->where('barcode', $item['barcode'])->dec('stock', $quantity)->update();
            }

            if ($payType == 2 && $memberId > 0) {
                Db::name('member')->where('id', $memberId)->dec('balance', $payAmount)->update();
            }

            Db::commit();
            return $this->jsonSuccess(['order_no' => $orderNo], '结算成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->jsonError('结算失败：' . $e->getMessage());
        }
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
}
