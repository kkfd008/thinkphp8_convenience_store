<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class Login extends BaseController
{
    protected $middleware = [];

    public function index()
    {
        if (Session::has('admin_user')) {
            return redirect('/index/index');
        }
        return View::fetch();
    }

    public function doLogin()
    {
        $username = $this->request->post('username', '');
        $password = $this->request->post('password', '');

        if (empty($username) || empty($password)) {
            return $this->jsonError('账号和密码不能为空');
        }

        $admin = Db::name('admin_user')->where('username', $username)->find();

        if (!$admin) {
            return $this->jsonError('账号不存在');
        }

        if (md5($password) !== $admin['password']) {
            return $this->jsonError('密码错误');
        }

        Session::set('admin_user', $admin);

        return $this->jsonSuccess([], '登录成功');
    }

    public function doLogout()
    {
        Session::clear();
        return redirect('/login');
    }
}
