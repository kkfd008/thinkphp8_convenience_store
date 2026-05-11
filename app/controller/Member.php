<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\View;

class Member extends BaseController
{
    protected $middleware = ['auth'];

    public function cateList()
    {
        $list = Db::name('member_cate')->order('id asc')->select()->toArray();
        View::assign(array_merge($this->assignAdminUser(), [
            'menus' => $this->getMenus(),
            'list'  => $list,
        ]));
        return View::fetch();
    }

    public function cateAdd()
    {
        $data = $this->request->post();
        Db::name('member_cate')->insert([
            'name'        => $data['name'] ?? '',
            'discount'    => floatval($data['discount'] ?? 1),
            'create_time' => time(),
        ]);
        return $this->jsonSuccess([], '新增成功');
    }

    public function cateEdit()
    {
        $data = $this->request->post();
        $id   = intval($data['id'] ?? 0);
        if ($id <= 0) return $this->jsonError('参数错误');
        Db::name('member_cate')->where('id', $id)->update([
            'name'     => $data['name'] ?? '',
            'discount' => floatval($data['discount'] ?? 1),
        ]);
        return $this->jsonSuccess([], '编辑成功');
    }

    public function cateDelete()
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) return $this->jsonError('参数错误');
        $memberCount = Db::name('member')->where('cate_id', $id)->count();
        if ($memberCount > 0) return $this->jsonError('该分类下有会员，不可删除');
        Db::name('member_cate')->where('id', $id)->delete();
        return $this->jsonSuccess([], '删除成功');
    }

    public function index()
    {
        $keyword = $this->request->get('keyword', '');
        $query = Db::name('member')->alias('m')
            ->leftJoin('member_cate mc', 'm.cate_id = mc.id')
            ->field('m.*, mc.name as cate_name, mc.discount');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('m.name', 'like', "%{$keyword}%")
                  ->whereOr('m.phone', 'like', "%{$keyword}%");
            });
        }

        $list = $query->order('m.id desc')->select()->toArray();
        $cates = Db::name('member_cate')->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'   => $this->getMenus(),
            'list'    => $list,
            'keyword' => $keyword,
            'cates'   => $cates,
        ]));
        return View::fetch();
    }

    public function add()
    {
        $data  = $this->request->post();
        $phone = $data['phone'] ?? '';

        $exist = Db::name('member')->where('phone', $phone)->find();
        if ($exist) {
            return $this->jsonError('该手机号已存在');
        }

        Db::name('member')->insert([
            'name'        => $data['name'] ?? '',
            'phone'       => $phone,
            'cate_id'     => intval($data['cate_id'] ?? 0),
            'balance'     => floatval($data['balance'] ?? 0),
            'remark'      => $data['remark'] ?? '',
            'create_time' => time(),
        ]);
        return $this->jsonSuccess([], '新增成功');
    }

    public function edit()
    {
        $data  = $this->request->post();
        $id    = intval($data['id'] ?? 0);
        if ($id <= 0) return $this->jsonError('参数错误');

        $phone = $data['phone'] ?? '';
        $exist = Db::name('member')->where('phone', $phone)->where('id', '<>', $id)->find();
        if ($exist) {
            return $this->jsonError('该手机号已被使用');
        }

        Db::name('member')->where('id', $id)->update([
            'name'    => $data['name'] ?? '',
            'phone'   => $phone,
            'cate_id' => intval($data['cate_id'] ?? 0),
            'remark'  => $data['remark'] ?? '',
        ]);
        return $this->jsonSuccess([], '编辑成功');
    }

    public function delete()
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) return $this->jsonError('参数错误');

        $orderCount = Db::name('order')->where('member_id', $id)->count();
        if ($orderCount > 0) {
            return $this->jsonError('该会员存在交易记录，禁止删除');
        }

        Db::name('member')->where('id', $id)->delete();
        return $this->jsonSuccess([], '删除成功');
    }

    public function recharge()
    {
        $members = Db::name('member')->order('id desc')->select()->toArray();
        View::assign(array_merge($this->assignAdminUser(), [
            'menus'   => $this->getMenus(),
            'members' => $members,
        ]));
        return View::fetch();
    }

    public function doRecharge()
    {
        $data     = $this->request->post();
        $memberId = intval($data['member_id'] ?? 0);
        $amount   = floatval($data['amount'] ?? 0);

        if ($memberId <= 0) return $this->jsonError('请选择会员');
        if ($amount <= 0) return $this->jsonError('充值金额必须大于0');

        $admin  = session('admin_user');
        $member = Db::name('member')->where('id', $memberId)->find();
        if (!$member) return $this->jsonError('会员不存在');

        $beforeBalance = floatval($member['balance']);
        $afterBalance  = $beforeBalance + $amount;

        Db::startTrans();
        try {
            Db::name('member_recharge')->insert([
                'member_id'      => $memberId,
                'amount'         => $amount,
                'before_balance' => $beforeBalance,
                'after_balance'  => $afterBalance,
                'operator_id'    => $admin['id'],
                'create_time'    => time(),
            ]);

            Db::name('member')->where('id', $memberId)->inc('balance', $amount)->update();

            Db::commit();
            return $this->jsonSuccess(['after_balance' => $afterBalance], '充值成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->jsonError('充值失败');
        }
    }

    public function rechargeLog()
    {
        $memberId  = $this->request->get('member_id', '');
        $startDate = $this->request->get('start_date', '');
        $endDate   = $this->request->get('end_date', '');

        $query = Db::name('member_recharge')->alias('mr')
            ->leftJoin('member m', 'mr.member_id = m.id')
            ->field('mr.*, m.name as member_name, m.phone as member_phone');

        if ($memberId !== '') {
            $query->where('mr.member_id', intval($memberId));
        }
        if ($startDate !== '') {
            $query->where('mr.create_time', '>=', strtotime($startDate));
        }
        if ($endDate !== '') {
            $query->where('mr.create_time', '<', strtotime($endDate) + 86400);
        }

        $list = $query->order('mr.id desc')->select()->toArray();
        $members = Db::name('member')->order('id desc')->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'     => $this->getMenus(),
            'list'      => $list,
            'member_id' => $memberId,
            'start_date'=> $startDate,
            'end_date'  => $endDate,
            'members'   => $members,
        ]));
        return View::fetch();
    }

    public function export()
    {
        $list = Db::name('member')->alias('m')
            ->leftJoin('member_cate mc', 'm.cate_id = mc.id')
            ->field('m.*, mc.name as cate_name')
            ->select()->toArray();

        $headers = ['ID', '姓名', '手机号', '分类', '余额', '备注', '创建时间'];
        $data = [];
        foreach ($list as $row) {
            $data[] = [
                $row['id'], $row['name'], $row['phone'], $row['cate_name'],
                $row['balance'], $row['remark'], date('Y-m-d H:i:s', $row['create_time']),
            ];
        }
        return $this->downloadExcel($headers, $data, '会员列表');
    }

}
