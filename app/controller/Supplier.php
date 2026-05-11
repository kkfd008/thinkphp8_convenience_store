<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\View;

class Supplier extends BaseController
{
    protected $middleware = ['auth'];

    public function index()
    {
        $keyword  = $this->request->get('keyword', '');
        $status   = $this->request->get('status', '');

        $query = Db::name('supplier');
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->whereOr('phone', 'like', "%{$keyword}%");
            });
        }
        if ($status !== '') {
            $query->where('status', intval($status));
        }
        $list = $query->order('id desc')->select()->toArray();

        View::assign(array_merge($this->assignAdminUser(), [
            'menus'   => $this->getMenus(),
            'list'    => $list,
            'keyword' => $keyword,
            'status'  => $status,
        ]));
        return View::fetch();
    }

    public function add()
    {
        $data = $this->request->post();
        Db::name('supplier')->insert([
            'name'    => $data['name'] ?? '',
            'contact' => $data['contact'] ?? '',
            'phone'   => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'remark'  => $data['remark'] ?? '',
            'status'  => intval($data['status'] ?? 1),
            'create_time' => time(),
        ]);
        return $this->jsonSuccess([], '新增成功');
    }

    public function edit()
    {
        $data = $this->request->post();
        $id   = intval($data['id'] ?? 0);
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }
        Db::name('supplier')->where('id', $id)->update([
            'name'    => $data['name'] ?? '',
            'contact' => $data['contact'] ?? '',
            'phone'   => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'remark'  => $data['remark'] ?? '',
            'status'  => intval($data['status'] ?? 1),
        ]);
        return $this->jsonSuccess([], '编辑成功');
    }

    public function delete()
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) {
            return $this->jsonError('参数错误');
        }
        $purchaseCount = Db::name('purchase')->where('supplier_id', $id)->count();
        if ($purchaseCount > 0) {
            return $this->jsonError('该供货商已关联进货单，禁止删除');
        }
        Db::name('supplier')->where('id', $id)->delete();
        return $this->jsonSuccess([], '删除成功');
    }

    public function toggleStatus()
    {
        $id = intval($this->request->post('id', 0));
        $status = intval($this->request->post('status', 1));
        Db::name('supplier')->where('id', $id)->update(['status' => $status]);
        return $this->jsonSuccess([], '操作成功');
    }

    public function export()
    {
        $list = Db::name('supplier')->select()->toArray();
        $headers = ['ID', '名称', '联系人', '电话', '地址', '备注', '状态', '创建时间'];
        $data    = [];
        foreach ($list as $row) {
            $data[] = [
                $row['id'],
                $row['name'],
                $row['contact'],
                $row['phone'],
                $row['address'],
                $row['remark'],
                $row['status'] == 1 ? '启用' : '禁用',
                date('Y-m-d H:i:s', $row['create_time']),
            ];
        }
        return $this->downloadExcel($headers, $data, '供货商列表');
    }

    public function import()
    {
        $file = $this->request->file('file');
        if (!$file) return $this->jsonError('请选择文件');

        $ext = strtolower($file->getOriginalExtension());
        if (!in_array($ext, ['xls', 'xlsx'])) return $this->jsonError('仅支持 Excel 文件');

        $savePath = runtime_path() . 'import_' . time() . '.' . $ext;
        $file->move(dirname($savePath), basename($savePath));

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($ext === 'xls' ? 'Xls' : 'Xlsx');
        $spreadsheet = $reader->load($savePath);
        $rows = $spreadsheet->getActiveSheet()->toArray();
        unlink($savePath);
        array_shift($rows);

        $successCount = 0;
        $failList = [];
        $existNames = array_column(Db::name('supplier')->field('name')->select()->toArray(), 'name');
        $importedNames = [];

        foreach ($rows as $i => $row) {
            $name    = trim($row[0] ?? '');
            $contact = trim($row[1] ?? '');
            $phone   = trim($row[2] ?? '');
            $address = trim($row[3] ?? '');
            $remark  = trim($row[4] ?? '');
            $status  = intval($row[5] ?? 1);

            if (empty($name)) {
                $failList[] = "第" . ($i + 2) . "行：名称不能为空";
                continue;
            }
            if (in_array($name, $existNames, true) || in_array($name, $importedNames, true)) {
                $failList[] = "第" . ($i + 2) . "行：名称 {$name} 已存在";
                continue;
            }
            $importedNames[] = $name;

            Db::name('supplier')->insert([
                'name'        => $name,
                'contact'     => $contact,
                'phone'       => $phone,
                'address'     => $address,
                'remark'      => $remark,
                'status'      => $status ?: 1,
                'create_time' => time(),
            ]);
            $successCount++;
        }

        return $this->jsonSuccess([
            'success' => $successCount,
            'fail'    => count($failList),
            'details' => $failList,
        ], "导入完成：成功 {$successCount} 条，失败 " . count($failList) . " 条");
    }

    public function downloadTemplate()
    {
        $headers = ['名称', '联系人', '电话', '地址', '备注', '状态(1启用0禁用)'];
        $this->downloadExcel($headers, [], '供货商导入模板');
    }

}
