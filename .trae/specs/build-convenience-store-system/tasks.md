# Tasks

## 第一阶段：项目初始化与基础设施

- [ ] Task 1: 创建 ThinkPHP 8 项目骨架
  - 使用 `composer create-project topthink/think newbld` 在当前目录创建项目（注意处理非空目录）
  - 确认 PHP ≥ 8.0，开启 pdo_sqlite 扩展
  - 验证项目可正常访问

- [ ] Task 2: 配置 SQLite3 数据库连接
  - 修改 `.env` 数据库配置为 sqlite
  - 修改 `config/database.php` 配置 SQLite 连接，数据库文件路径 `database/shop.db`
  - 确保 `database/` 目录存在且有写入权限

- [ ] Task 3: 创建数据库建表 SQL 与种子数据
  - 编写 `database/schema.sql`，包含全部 12 张表的 CREATE TABLE 语句（admin_user、role、auth_rule、supplier、goods、purchase、purchase_detail、order、order_detail、member_cate、member、member_recharge）
  - 种子数据：插入默认角色（超级管理员/店长/收银员）、默认权限规则、默认管理员账号 admin/admin123
  - 通过 ThinkPHP 迁移或手动执行 SQL 初始化数据库

- [ ] Task 4: 引入 Layui 2.9.10 与 ECharts
  - 下载 Layui 2.9.10，放入 `public/static/layui/`
  - 下载 ECharts，放入 `public/static/echarts/`
  - 创建公共布局模板 `view/layout.html`（左侧导航 + 顶部栏 + 内容区，使用 Layui layout 组件）

- [ ] Task 5: 配置基础设置
  - 配置 `config/app.php`（调试模式、时区 Asia/Shanghai）
  - 配置 `config/view.php`（模板引擎配置、标签替换 `{__STATIC__}` → `/static`）
  - 配置 `config/middleware.php`（注册全局权限中间件或路由中间件）
  - 创建 `app/BaseController.php`（公共基类，初始化登录用户信息）

## 第二阶段：权限认证模块

- [ ] Task 6: 实现登录功能
  - 创建 `view/login/index.html`（Layui 表单，居中卡片样式）
  - 创建 `app/controller/Login.php`：`index()` 展示登录页，`doLogin()` 验证账号密码（MD5），写入 session
  - 创建 `doLogout()` 清除 session，跳转登录页
  - 路由配置：`/login` → Login/index

- [ ] Task 7: 实现权限中间件
  - 创建 `app/middleware/Auth.php`
  - `handle()` 方法：检查 session 中是否有 admin_user → 无则跳转 `/login`
  - 根据当前路由的控制器/方法名，匹配 `auth_rule.name`，校验当前用户 `role.rules` 是否包含该权限ID
  - 无权限返回 JSON `{code: 403, msg: '无权限访问'}`
  - 将当前用户信息注入 request 供控制器使用

- [ ] Task 8: 实现首页仪表盘框架
  - 创建 `app/controller/Index.php`：`index()` 方法
  - 创建 `view/index/index.html`（预留给第十一阶段填充 ECharts，当前先展示欢迎信息）
  - 左侧菜单动态渲染：读 `auth_rule` 树形结构，根据当前角色 `rules` 过滤可见菜单
  - 更新 `view/layout.html` 集成菜单渲染逻辑

## 第三阶段：权限配置管理（超级管理员专属）

- [ ] Task 9: 实现权限规则管理
  - 创建 `app/controller/Auth.php`：`ruleList()`、`ruleAdd()`、`ruleEdit()`、`ruleDelete()`
  - 创建 `view/auth/rule.html`：Layui treeTable 展示无限级树形
  - 新增/编辑子权限弹窗表单（pid 选择父级、title、name、icon、sort）
  - 删除时级联删除所有子权限

- [ ] Task 10: 实现角色管理
  - `app/controller/Auth.php`：`roleList()`、`roleAdd()`、`roleEdit()`、`roleDelete()`
  - `view/auth/role.html`：Layui table 列表 + 新增/编辑弹窗
  - 编辑弹窗内嵌权限树（Layui tree + 复选框），选中状态回显 `role.rules`
  - 保存时 rules 以逗号分隔存储

- [ ] Task 11: 实现管理员账号管理
  - `app/controller/Auth.php`：`adminList()`、`adminAdd()`、`adminEdit()`、`adminDelete()`
  - `view/auth/admin.html`：Layui table 列表（含角色名称展示）
  - 新增/编辑弹窗：选择角色（下拉框从 role 表读取）
  - 密码处理：新增时 MD5 加密，编辑时可留空不修改密码

## 第四阶段：供货商管理模块

- [ ] Task 12: 实现供货商 CRUD
  - 创建 `app/model/Supplier.php`
  - 创建 `app/controller/Supplier.php`：`index()`、`add()`、`edit()`、`delete()`、`toggleStatus()`
  - 创建 `view/supplier/index.html`：Layui table（搜索：名称/电话、状态筛选、分页）
  - 创建 `view/supplier/edit.html` 或使用弹窗表单（名称、联系人、电话、地址、备注、状态）
  - 删除校验：查询 purchase 表是否有关联该 supplier_id 的记录 → 有关联则返回错误提示
  - 状态切换：启用/禁用按钮

## 第五阶段：商品管理模块

- [ ] Task 13: 实现商品 CRUD
  - 创建 `app/model/Goods.php`
  - 创建 `app/controller/Goods.php`：`index()`、`add()`、`edit()`、`delete()`
  - 创建 `view/goods/index.html`：Layui table（搜索：名称/条码/分类、分页）
  - 创建 `view/goods/edit.html`：表单（名称、条码[含随机生成按钮]、进货价、零售价、分类、stock_min、stock_max）
  - 前端随机生成条码：13位数字，AJAX 校验唯一性
  - 编辑时：查询 purchase_detail + order_detail 是否关联该 barcode → 有关联则条码置灰不可修改
  - 删除校验：查询 purchase_detail + order_detail 是否有关联 → 有关联则禁止删除

- [ ] Task 14: 实现商品分类管理
  - `app/controller/Goods.php`：`cateList()`、`cateAdd()`、`cateDelete()`
  - `view/goods/cate.html`：简单列表维护页面
  - 新增商品时分类下拉从 goods 表已有 cate 字段去重获取

- [ ] Task 15: 实现扫码快速查询
  - 列表页搜索框支持扫码枪输入 → 回车 → AJAX 搜索 barcode 字段精确匹配

- [ ] Task 16: 实现商品 Excel 批量导入
  - 创建 `app/controller/Goods.php`：`import()`、`downloadTemplate()`
  - 下载模板：生成含表头的 Excel 文件（商品名称、商品条码、进货价、零售价、库存数量、商品分类、stock_min、stock_max）
  - 上传解析：使用 PhpSpreadsheet 读取上传的 Excel
  - 校验逻辑：条码唯一性（与已有数据库对比+导入数据内部去重）、必填字段非空检查、跳过空行
  - 导入结果页：展示成功条数、失败条数及失败原因

## 第六阶段：进货管理模块

- [ ] Task 17: 实现手动新建进货单
  - 创建 `app/model/Purchase.php`、`app/model/PurchaseDetail.php`
  - 创建 `app/controller/Purchase.php`：`add()`、`doAdd()`
  - 创建 `view/purchase/add.html`
  - 选择供货商：Layui 下拉搜索框（仅显示启用状态的供货商）
  - 进货明细动态表格：搜索/选择商品（按条码/名称搜索）→ 自动填入商品名称、进货价、零售价 → 手动填写箱规、箱数、散件数 → 自动计算金额
  - 明细金额公式：`purchase_price × (box_spec × box_count + piece_count)`
  - 进货单号生成规则：`JH` + 年月日 + 3位序号（同日自增）
  - 保存事务：写入 purchase → 写入 purchase_detail 多条 → 累加 goods.stock（goods.stock += box_spec × box_count + piece_count）
  - 保存后跳转进货列表页

- [ ] Task 18: 实现进货历史查询与明细
  - `app/controller/Purchase.php`：`index()`
  - `view/purchase/index.html`：Layui table（时间范围、供货商下拉筛选、单号搜索、分页）
  - `view/purchase/detail.html`：展示进货主表信息 + 明细列表
  - `app/controller/Purchase.php`：`detail()`

- [ ] Task 19: 实现进货单 Excel 批量导入
  - `app/controller/Purchase.php`：`import()`、`downloadTemplate()`
  - 下载模板：供货商名称、商品条码、商品名称、进货价、零售价、箱规、箱数、进货散件数量
  - 上传解析：校验供货商名称有效性、商品条码有效性
  - 无效数据跳过并记录原因
  - 有效数据按供货商分组生成进货单，自动入库更新库存
  - 展示导入结果

- [ ] Task 20: 实现进货数据导出
  - `app/controller/Purchase.php`：`export()`
  - 导出为 XLSX，含进货主表 + 明细信息

## 第七阶段：库存管理与预警模块

- [ ] Task 21: 实现库存总览
  - 创建 `app/controller/Stock.php`：`index()`
  - 创建 `view/stock/index.html`
  - Layui table 展示所有商品：名称、条码、分类、库存、stock_min、stock_max
  - 库存低于 stock_min 行标红（CSS class），高于 stock_max 行标黄
  - 支持内联编辑 stock_min / stock_max（点击单元格变输入框）
  - 搜索：名称/条码/分类筛选、分页
  - `app/controller/Stock.php`：`updateThreshold()` 更新预警阈值

- [ ] Task 22: 实现库存预警页
  - `app/controller/Stock.php`：`warning()`
  - `view/stock/warning.html`
  - 预警类型筛选按钮：全部 / 低于最小库存(stock < stock_min) / 高于最大库存(stock > stock_max)
  - 表格展示预警商品，全部标红突出
  - 每行操作按钮：「编辑阈值」（弹窗修改 stock_min/stock_max）、「一键补货」（跳转 purchase/add 并预填商品信息）

- [ ] Task 23: 实现库存数据导出
  - `app/controller/Stock.php`：`export()`

## 第八阶段：收银台核心模块

- [ ] Task 24: 实现收银台主界面
  - 创建 `app/controller/Cashier.php`：`index()`
  - 创建 `view/cashier/index.html`
  - 布局：左侧搜索/扫码区（条码输入框自动聚焦 + 商品搜索结果列表），右侧购物车表格
  - 购物车展示：商品名称、单价、数量（+/- 按钮）、小计、删除
  - 底部汇总：商品总数、原价总金额、折扣金额、实付金额
  - 支付方式 Radio：现金 / 会员余额
  - 会员选择：搜索框（手机号/姓名）、选择后自动带出折扣率

- [ ] Task 25: 实现购物车操作
  - `app/controller/Cashier.php`：`searchGoods()` 按条码/名称搜索商品返回 JSON
  - 扫码加购：条码输入框回车 → 调用 searchGoods → 找到商品加入购物车（购物车纯前端存储，数组对象）
  - 已存在商品数量 +1
  - 修改数量时校验不超过库存
  - 选择会员时重新计算折扣金额：`pay_amount = total_amount × member_cate.discount`

- [ ] Task 26: 实现结算下单
  - `app/controller/Cashier.php`：`doCheckout()`
  - 参数：购物车 JSON 数组、支付方式、会员ID
  - 服务端二次校验：库存充足性（逐商品对比 goods.stock）
  - 会员余额支付：校验 member.balance ≥ pay_amount
  - 订单号生成：`DD` + 年月日 + 序号
  - 事务写入：order + order_detail → 逐商品扣减 goods.stock → 余额支付则扣减 member.balance
  - 返回 JSON：`{code: 0, msg: '结算成功', data: {order_no: 'xxx'}}`
  - 前端：清空购物车，提示成功，可打印小票（console.log 模拟或简单弹窗）

## 第九阶段：订单管理模块

- [ ] Task 27: 实现订单列表与明细
  - 创建 `app/model/Order.php`、`app/model/OrderDetail.php`
  - 创建 `app/controller/Order.php`：`index()`、`detail()`
  - 创建 `view/order/index.html`：筛选（时间范围 laydate、订单号、会员）、列表、分页
  - 列表展示：订单号、原价、折扣、实付、支付方式、会员名称、收银员、时间
  - 收银员仅看本人订单（role_id=3 时自动过滤 operator_id）
  - `view/order/detail.html`：订单主信息 + 明细列表

- [ ] Task 28: 实现订单数据导出
  - `app/controller/Order.php`：`export()`

## 第十阶段：会员管理模块

- [ ] Task 29: 实现会员分类管理
  - 创建 `app/model/MemberCate.php`
  - `app/controller/Member.php`：`cateList()`、`cateAdd()`、`cateEdit()`、`cateDelete()`
  - `view/member/cate.html`：列表 + 新增/编辑弹窗（名称、折扣率）

- [ ] Task 30: 实现会员档案管理
  - 创建 `app/model/Member.php`
  - `app/controller/Member.php`：`index()`、`add()`、`edit()`、`delete()`
  - `view/member/index.html`：搜索（姓名/手机号）、列表、分页
  - `view/member/edit.html` 或弹窗表单：姓名、手机号、分类下拉、备注
  - 手机号唯一性校验（前后端双重校验）
  - 删除校验：查询 order 表是否有关联 member_id → 有关联禁止删除

- [ ] Task 31: 实现会员充值
  - `app/controller/Member.php`：`recharge()`、`doRecharge()`
  - `view/member/recharge.html`：选择会员（搜索下拉）、充值金额输入、确认按钮
  - 创建 `app/model/MemberRecharge.php`
  - 充值逻辑：查询当前 balance → 写入 member_recharge（before_balance/after_balance）→ 更新 member.balance += amount
  - 展示充值成功提示

- [ ] Task 32: 实现充值记录查询
  - `app/controller/Member.php`：`rechargeLog()`
  - `view/member/recharge_log.html`：筛选（会员搜索、时间范围）、列表（会员名、金额、充值前/后余额、操作员、时间）

- [ ] Task 33: 实现会员数据导出
  - `app/controller/Member.php`：`export()`

## 第十一阶段：数据统计模块

- [ ] Task 34: 实现统计仪表盘
  - 完善 `app/controller/Index.php`：`index()` 查询统计数据
  - 完善 `view/index/index.html`
  - 数字卡片：今日营收（SUM order.pay_amount WHERE 今天）、累计营收、今日订单数、累计订单数
  - 基础数据：商品总数、会员总数、供货商总数
  - ECharts 近15日销售趋势图（折线图，X轴日期，Y轴金额）
  - ECharts 近15日进货趋势图（折线图，同上叠加或分开）
  - ECharts 商品销量 TOP10（横向柱状图，从 order_detail 按 barcode 聚合 SUM(quantity) 取前10，关联 goods.name）

## 第十二阶段：Excel 导入导出通用能力

- [ ] Task 35: 引入 PhpSpreadsheet
  - `composer require phpoffice/phpspreadsheet`
  - 创建 `app/service/ExportService.php` 封装通用导出方法
  - 封装 `ExportService.php`：`downloadExcel($data, $headers, $filename)` 方法
  - 时间戳转日期：`date('Y-m-d H:i:s', $timestamp)`
  - 金额格式化：`number_format($amount, 2)`

- [ ] Task 36: 实现各模块导出接口（汇总串联）
  - 确保以下导出功能均已实现：商品导出(Goods/export)、供货商导出(Supplier/export)、进货单导出(Purchase/export)、订单导出(Order/export)、会员导出(Member/export)、库存导出(Stock/export)
  - 验证所有导出文件 XLSX 格式正确，时间列为日期格式，金额为两位小数

## 第十三阶段：联调测试与收尾

- [ ] Task 37: 全流程业务联调
  - 启动 PHP 内置服务器：`php think run` 或 `php -S localhost:8000 -t public`
  - 使用 admin/admin123 登录
  - 测试完整流程：创建供货商 → 创建商品 → 创建进货单 → 查看库存 → 收银台结算 → 查看订单 → 创建会员 → 会员充值 → 会员消费 → 查看统计
  - 切换店长/收银员角色验证权限边界

- [ ] Task 38: Bug 修复
  - 根据联调结果修复发现的问题

- [ ] Task 39: 创建简要部署说明
  - 在项目根目录创建 `README.md`（仅包含部署步骤：PHP版本要求、扩展要求、启动命令、默认账号密码）

# Task Dependencies

| Task | 依赖 |
|------|------|
| Task 2 | Task 1 |
| Task 3 | Task 2 |
| Task 4 | Task 1 |
| Task 5 | Task 1 |
| Task 6 | Task 3, Task 5 |
| Task 7 | Task 6 |
| Task 8 | Task 6, Task 7 |
| Task 9 | Task 8 |
| Task 10 | Task 9 |
| Task 11 | Task 6 |
| Task 12 | Task 7 |
| Task 13 | Task 7, Task 12 |
| Task 14 | Task 13 |
| Task 15 | Task 13 |
| Task 16 | Task 13, Task 35 |
| Task 17 | Task 13, Task 12 |
| Task 18 | Task 17 |
| Task 19 | Task 17, Task 35 |
| Task 20 | Task 18, Task 35 |
| Task 21 | Task 13, Task 17 |
| Task 22 | Task 21 |
| Task 23 | Task 21, Task 35 |
| Task 24 | Task 7, Task 13 |
| Task 25 | Task 24 |
| Task 26 | Task 25 |
| Task 27 | Task 26 |
| Task 28 | Task 27, Task 35 |
| Task 29 | Task 7 |
| Task 30 | Task 29 |
| Task 31 | Task 30 |
| Task 32 | Task 31 |
| Task 33 | Task 30, Task 35 |
| Task 34 | Task 18, Task 27 |
| Task 35 | 无 |
| Task 36 | 各模块 Task |
| Task 37 | 所有模块 Task 完成 |
| Task 38 | Task 37 |
| Task 39 | 全部完成 |
