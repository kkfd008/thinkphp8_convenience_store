<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class Index extends BaseController
{
    protected $middleware = ['auth'];

    public function index()
    {
        $admin = $this->adminUser;
        if (!$admin) {
            return redirect('/login');
        }
        $menus = $this->buildMenus($admin);

        $todayStart = strtotime(date('Y-m-d'));

        $todayAmount = Db::name('order')->where('create_time', '>=', $todayStart)->sum('pay_amount') ?: 0;
        $todayOrders = Db::name('order')->where('create_time', '>=', $todayStart)->count();
        $totalAmount = Db::name('order')->sum('pay_amount') ?: 0;
        $totalOrders = Db::name('order')->count();

        $goodsCount   = Db::name('goods')->count();
        $memberCount  = Db::name('member')->count();
        $supplierCount = Db::name('supplier')->count();

        $trendDates = [];
        $trendSales = [];
        $trendPurchases = [];
        for ($i = 14; $i >= 0; $i--) {
            $dayStart = strtotime(date('Y-m-d', strtotime("-{$i} days")));
            $dayEnd   = $dayStart + 86400;
            $trendDates[] = date('m-d', $dayStart);
            $trendSales[] = round(Db::name('order')->where('create_time', '>=', $dayStart)->where('create_time', '<', $dayEnd)->sum('pay_amount') ?: 0, 2);
            $trendPurchases[] = round(Db::name('purchase')->where('create_time', '>=', $dayStart)->where('create_time', '<', $dayEnd)->sum('total_amount') ?: 0, 2);
        }

        $top10List = Db::name('order_detail')
            ->field('barcode, goods_name, SUM(quantity) as total_qty')
            ->group('barcode')
            ->order('total_qty', 'desc')
            ->limit(10)
            ->select()->toArray();

        $top10Names  = array_column($top10List, 'goods_name');
        $top10Counts = array_column($top10List, 'total_qty');

        View::assign([
            'admin_username'  => $admin['username'] ?? '',
            'admin'           => $admin,
            'menus'           => $menus,
            'today_amount'    => number_format($todayAmount, 2),
            'today_orders'    => $todayOrders,
            'total_amount'    => number_format($totalAmount, 2),
            'total_orders'    => $totalOrders,
            'goods_count'     => $goodsCount,
            'member_count'    => $memberCount,
            'supplier_count'  => $supplierCount,
            'trend_dates'     => $trendDates,
            'trend_sales'     => $trendSales,
            'trend_purchases' => $trendPurchases,
            'top10_names'     => $top10Names,
            'top10_counts'    => $top10Counts,
        ]);

        return View::fetch();
    }

    private function buildMenus($admin)
    {
        $role = Db::name('role')->where('id', $admin['role_id'])->find();
        $rulesArr = [];
        if ($role && !empty($role['rules'])) {
            $rulesArr = explode(',', $role['rules']);
        }

        $allRules = Db::name('auth_rule')->order('sort asc, id asc')->select()->toArray();
        $tree = $this->buildTree($allRules, 0, $rulesArr);

        return $tree;
    }

    private function buildTree($rules, $pid, $allowedRules)
    {
        $tree = [];
        foreach ($rules as $rule) {
            if ($rule['pid'] == $pid) {
                if (!in_array((string)$rule['id'], $allowedRules, true)) {
                    continue;
                }
                $item = [
                    'title' => $rule['title'],
                    'icon'  => $rule['icon'] ?? '',
                    'url'   => !empty($rule['name']) ? url($rule['name'])->build() : '#',
                ];

                $children = $this->buildTree($rules, $rule['id'], $allowedRules);
                if (!empty($children)) {
                    $item['children'] = $children;
                }

                $tree[] = $item;
            }
        }
        return $tree;
    }
}
