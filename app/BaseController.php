<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\Validate;
use think\facade\Db;
use think\facade\Session;
use think\facade\View;

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

    protected function getMenus()
    {
        $admin = Session::get('admin_user');
        $role = Db::name('role')->where('id', $admin['role_id'])->find();
        $rulesArr = $role && !empty($role['rules']) ? explode(',', $role['rules']) : [];
        $allRules = Db::name('auth_rule')->order('sort asc, id asc')->select()->toArray();
        return $this->buildMenuTree($allRules, 0, $rulesArr);
    }

    protected function buildMenuTree($rules, $pid, $allowedRules)
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
                $children = $this->buildMenuTree($rules, $rule['id'], $allowedRules);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    protected function downloadExcel($headers, $data, $filename)
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
