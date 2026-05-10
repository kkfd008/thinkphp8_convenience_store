<?php
declare (strict_types=1);

namespace app\middleware;

use think\facade\Db;
use think\facade\Session;

class Auth
{
    public function handle($request, \Closure $next)
    {
        if (!Session::has('admin_user')) {
            if ($request->isAjax() || $request->isPost()) {
                return response(json_encode(['code' => 403, 'msg' => '请先登录']), 403, ['Content-Type' => 'application/json']);
            }
            return redirect('/login');
        }

        $admin = Session::get('admin_user');
        $request->adminUser = $admin;

        $controller = $request->controller();
        $action     = $request->action();
        $ruleName   = $controller . '/' . $action;

        $publicActions = ['Index/index', 'Index/welcome'];
        if (in_array($ruleName, $publicActions)) {
            return $next($request);
        }

        $rule = Db::name('auth_rule')->where('name', $ruleName)->find();
        if (!$rule) {
            $parentRuleName = $controller . '/index';
            $rule = Db::name('auth_rule')->where('name', $parentRuleName)->find();
        }

        if ($rule) {
            $role = Db::name('role')->where('id', $admin['role_id'])->find();
            if ($role && !empty($role['rules'])) {
                $rulesArr = explode(',', $role['rules']);
                if (!in_array((string)$rule['id'], $rulesArr, true)) {
                    if ($request->isAjax() || $request->isPost()) {
                        return response(json_encode(['code' => 403, 'msg' => '无权限访问']), 403, ['Content-Type' => 'application/json']);
                    }
                    return response('<div style="text-align:center;padding:50px;color:#FF5722;"><h2>403 - 无权限访问</h2></div>', 403);
                }
            }
        }

        return $next($request);
    }
}
