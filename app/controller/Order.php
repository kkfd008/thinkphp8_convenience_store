<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
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

        $admin = session('admin_user');

        $query = Db::name('order')->alias('o')
            ->leftJoin('member m', 'o.member_id = m.id')
            ->field('o.*, m.name as member_name, m.phone as member_phone');

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

        $list = $query->order('o.id desc')->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'      => $this->getMenus(),
            'list'       => $list,
            'keyword'    => $keyword,
            'member_id'  => $memberId,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]));
        return View::fetch();
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
            ->field('o.*, m.name as member_name')
            ->select()->toArray();

        $headers = ['订单号', '原价', '折扣', '实付', '支付方式', '会员', '收银员ID', '时间'];
        $data = [];
        foreach ($list as $row) {
            $data[] = [
                $row['order_no'], $row['total_amount'], $row['discount_amount'],
                $row['pay_amount'], $row['pay_type'] == 1 ? '现金' : '会员余额',
                $row['member_name'] ?: '-', $row['operator_id'],
                date('Y-m-d H:i:s', $row['create_time']),
            ];
        }
        return $this->downloadExcel($headers, $data, '订单列表');
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
