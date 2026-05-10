<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\Validate;
use think\facade\Session;

abstract class BaseController
{
    protected $request;
    protected $app;
    protected $adminUser = null;
    protected $batchValidate = false;
    protected $middleware = [];

    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
        $this->initialize();
    }

    protected function initialize()
    {
        $this->adminUser = Session::get('admin_user');
    }

    protected function assignAdminUser()
    {
        if ($this->adminUser) {
            return [
                'admin'          => $this->adminUser,
                'admin_id'       => $this->adminUser['id'] ?? 0,
                'admin_username' => $this->adminUser['username'] ?? '',
                'admin_role_id'  => $this->adminUser['role_id'] ?? 0,
            ];
        }
        return ['admin' => [], 'admin_id' => 0, 'admin_username' => '', 'admin_role_id' => 0];
    }

    protected function jsonSuccess($data = [], $msg = '操作成功')
    {
        return json(['code' => 0, 'msg' => $msg, 'data' => $data]);
    }

    protected function jsonError($msg = '操作失败', $code = 1)
    {
        return json(['code' => $code, 'msg' => $msg]);
    }

    protected function validate(array $data, string|array $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }
}
