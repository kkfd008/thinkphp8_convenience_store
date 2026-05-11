<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\View;

class Auth extends BaseController
{
    protected $middleware = ['auth'];

    public function ruleList()
    {
        $rules = Db::name('auth_rule')->order('sort asc, id asc')->select()->toArray();
        $tree  = $this->buildRuleTree($rules);

        View::assign(array_merge($this->assignAdminUser(), [
            'menus' => $this->getMenus(),
            'rules' => $tree,
        ]));
        return View::fetch();
    }

    public function ruleAdd()
    {
        $data = $this->request->post();
        Db::name('auth_rule')->insert([
            'pid'   => intval($data['pid'] ?? 0),
            'title' => $data['title'] ?? '',
            'name'  => $data['name'] ?? '',
            'icon'  => $data['icon'] ?? '',
            'sort'  => intval($data['sort'] ?? 0),
        ]);
        return $this->jsonSuccess([], '新增成功');
    }

    public function ruleEdit()
    {
        $data = $this->request->post();
        $id   = intval($data['id'] ?? 0);
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }
        Db::name('auth_rule')->where('id', $id)->update([
            'pid'   => intval($data['pid'] ?? 0),
            'title' => $data['title'] ?? '',
            'name'  => $data['name'] ?? '',
            'icon'  => $data['icon'] ?? '',
            'sort'  => intval($data['sort'] ?? 0),
        ]);
        return $this->jsonSuccess([], '编辑成功');
    }

    public function ruleDelete()
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }
        $this->deleteRuleCascade($id);
        return $this->jsonSuccess([], '删除成功');
    }

    private function deleteRuleCascade($pid)
    {
        $children = Db::name('auth_rule')->where('pid', $pid)->select();
        foreach ($children as $child) {
            $this->deleteRuleCascade($child['id']);
        }
        Db::name('auth_rule')->where('id', $pid)->delete();
    }

    public function roleList()
    {
        $roles = Db::name('role')->select()->toArray();
        $rules = Db::name('auth_rule')->order('sort asc, id asc')->select()->toArray();
        $tree  = $this->buildRuleTree($rules);

        View::assign(array_merge($this->assignAdminUser(), [
            'menus' => $this->getMenus(),
            'roles' => $roles,
            'ruleTree' => $tree,
        ]));
        return View::fetch();
    }

    public function roleAdd()
    {
        $data = $this->request->post();
        Db::name('role')->insert([
            'name'  => $data['name'] ?? '',
            'rules' => $data['rules'] ?? '',
        ]);
        return $this->jsonSuccess([], '新增成功');
    }

    public function roleEdit()
    {
        $data = $this->request->post();
        $id   = intval($data['id'] ?? 0);
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }
        Db::name('role')->where('id', $id)->update([
            'name'  => $data['name'] ?? '',
            'rules' => $data['rules'] ?? '',
        ]);
        return $this->jsonSuccess([], '编辑成功');
    }

    public function roleDelete()
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }
        Db::name('role')->where('id', $id)->delete();
        return $this->jsonSuccess([], '删除成功');
    }

    public function adminList()
    {
        $admins = Db::name('admin_user')
            ->alias('a')
            ->leftJoin('role r', 'a.role_id = r.id')
            ->field('a.*, r.name as role_name')
            ->select()->toArray();

        $roles = Db::name('role')->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'  => $this->getMenus(),
            'admins' => $admins,
            'roles'  => $roles,
        ]));
        return View::fetch();
    }

    public function adminAdd()
    {
        $data = $this->request->post();
        $username = $data['username'] ?? '';

        $exist = Db::name('admin_user')->where('username', $username)->find();
        if ($exist) {
            return $this->jsonError('账号已存在');
        }

        Db::name('admin_user')->insert([
            'username'    => $username,
            'password'    => md5($data['password'] ?? ''),
            'role_id'     => intval($data['role_id'] ?? 0),
            'create_time' => time(),
        ]);
        return $this->jsonSuccess([], '新增成功');
    }

    public function adminEdit()
    {
        $data = $this->request->post();
        $id   = intval($data['id'] ?? 0);
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }

        $update = [
            'username' => $data['username'] ?? '',
            'role_id'  => intval($data['role_id'] ?? 0),
        ];

        if (!empty($data['password'])) {
            $update['password'] = md5($data['password']);
        }

        Db::name('admin_user')->where('id', $id)->update($update);
        return $this->jsonSuccess([], '编辑成功');
    }

    public function adminDelete()
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }
        Db::name('admin_user')->where('id', $id)->delete();
        return $this->jsonSuccess([], '删除成功');
    }


    private function buildRuleTree($rules, $pid = 0)
    {
        $tree = [];
        foreach ($rules as $rule) {
            if ($rule['pid'] == $pid) {
                $rule['children'] = $this->buildRuleTree($rules, $rule['id']);
                $tree[] = $rule;
            }
        }
        return $tree;
    }
}
