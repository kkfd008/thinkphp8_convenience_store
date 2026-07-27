# Tasks

## 第一阶段：项目初始化与基础设施

- [x] Task 1: 创建 ThinkPHP 8 项目骨架
  - 使用 `composer create-project topthink/think` 创建项目
  - 确认 PHP ≥ 8.0，开启 pdo_sqlite 扩展
  - 验证项目可正常访问

- [x] Task 2: 配置 SQLite3/MySQL 双数据库驱动
  - 修改 `.env` 数据库配置，支持 `DB_DRIVER=sqlite` 和 `DB_DRIVER=mysql` 双模式
  - 修改 `config/database.php` 配置双驱动连接，SQLite 数据库文件路径 `database/shop.db`
  - 确保 `database/` 目录存在且有写入权限

- [x] Task 3: 创建数据库建表 SQL 与种子数据
  - 编写 `database/schema.sql`，包含全部 13 张表的 CREATE TABLE 语句
  - 种子数据：插入默认角色（超级管理员/店长/收银员）、22 条权限规则、默认管理员账号 admin/admin123
  - 三级会员分类：普通会员（98折）、金牌会员（95折）、钻石会员（90折）
  - 四类商品分类：饮料、零食、日用品、烟酒、调味品

- [x] Task 4: 引入 Layui 2.9.10 与 ECharts
  - Layui 2.9.10 放入 `public/static/layui/`
  - ECharts 放入 `public/static/echarts/`
  - 创建公共布局模板 `view/layout.html`（左侧导航 + 顶部栏 + 面包屑 + 内容区）

- [x] Task 5: 配置基础设置
  - 配置 `config/app.php`（调试模式、时区 Asia/Shanghai）
  - 配置 `config/view.php`（模板引擎、标签替换 `{__LAYUI__}`、`{__STATIC__}`）
  - 配置 `config/middleware.php`（注册路由中间件）
  - 创建 `app/BaseController.php`（公共基类）

## 第二阶段：权限认证模块

- [x] Task 6: 实现登录功能
  - 创建 `view/login/index.html`（Layui 表单，居中卡片样式，autocomplete 优化）
  - 创建 `app/controller/Login.php`：`index()` 展示登录页，`doLogin()` 验证 MD5 密码，写入 session
  - 创建 `doLogout()` 清除 session，跳转登录页
  - 路由配置：`/login` → Login/index

- [x] Task 7: 实现权限中间件
  - 创建 `app/middleware/Auth.php`
  - `handle()` 方法：检查 session 中的 admin_user → 无则跳转 `/login`
  - 根据当前路由的控制器/方法名，匹配 `auth_rule.name`，校验当前用户 `role.rules` 是否包含该权限ID
  - 无权限返回 JSON `{code: 403, msg: '无权限访问'}`
  - 将当前用户信息注入 request 供控制器和视图使用

- [x] Task 8: 实现首页仪表盘框架
  - 创建 `app/controller/Index.php`：`index()` 方法
  - 创建 `view/index/index.html`（ECharts 图表 + 统计卡片）
  - 左侧菜单动态渲染：读 `auth_rule` 树形结构，根据当前角色 `rules` 过滤可见菜单
  - 面包屑导航根据当前路径自动生成

## 第三阶段：权限配置管理（超级管理员专属）

- [x] Task 9: 实现权限规则管理
  - `app/controller/Auth.php`：`ruleList()`、`ruleAdd()`、`ruleEdit()`、`ruleDelete()`
  - 创建 `view/auth/rule_list.html`：Layui table 树形展示（递归缩进 + ├─ 前缀）
  - 新增/编辑子权限弹窗表单（pid 选择父级、title、name、icon、sort）
  - 删除时提示确认

- [x] Task 10: 实现角色管理
  - `app/controller/Auth.php`：`roleList()`、`roleAdd()`、`roleEdit()`、`roleDelete()`
  - 创建 `view/auth/role_list.html`：Layui table 列表 + 新增/编辑弹窗
  - 编辑弹窗内嵌权限树（checkbox 递归渲染），选中状态回显 `role.rules`
  - 保存时 rules 以逗号分隔存储

- [x] Task 11: 实现管理员账号管理
  - `app/controller/Auth.php`：`adminList()`、`adminAdd()`、`adminEdit()`、`adminDelete()`
  - 创建 `view/auth/admin_list.html`：Layui table 列表（含角色名称展示）
  - 新增/编辑弹窗：选择角色（下拉框从 role 表读取）
  - 密码处理：新增时 MD5 加密，编辑时可留空不修改密码

## 第四阶段：供货商管理模块

- [x] Task 12: 实现供货商 CRUD + 导入导出
  - 创建 `app/controller/Supplier.php`：`index()`、`add()`、`edit()`、`delete()`、`toggleStatus()`
  - 创建 `view/supplier/index.html`：Layui table（搜索：名称/电话、状态筛选）
  - 弹窗表单（名称、联系人、电话、地址、备注、状态）
  - 删除校验：查询 purchase 表是否关联该 supplier_id
  - 状态切换：启用/禁用按钮（`toggleStatus` 接口）
  - Excel 导入：`import()` 接口，upload 组件上传 xls/xlsx
  - Excel 导出：`export()` 接口
  - 模板下载：`downloadTemplate()` 接口

## 第五阶段：商品管理模块

- [x] Task 13: 实现商品 CRUD
  - 创建 `app/controller/Goods.php`：`index()`、`add()`、`edit()`、`delete()`
  - 创建 `view/goods/index.html`：Layui table（搜索：名称/条码/分类、checkbox 多选）
  - 弹窗表单（名称、条码[含随机生成按钮]、单位、箱规、进货价、零售价、分类下拉、stock_min、stock_max）
  - `genBarcode()` 接口：随机生成 13 位数字条码
  - `checkBarcode()` 接口：校验条码唯一性
  - 编辑时：关联进货/订单后条码 `readonly` 不可修改
  - 删除校验：关联进货/订单记录禁止删除

- [x] Task 14: 实现商品分类管理
  - `app/controller/Goods.php`：`cateList()`、`cateAdd()`、`cateEdit()`、`cateToggle()`
  - 创建 `view/goods/cate_list.html`：列表 + 新增/修改弹窗
  - 状态切换：显示/隐藏按钮（`cateToggle` 接口）

- [x] Task 15: 实现扫码快速查询
  - 列表页搜索框支持输入后回车搜索

- [x] Task 16: 实现商品 Excel 批量导入
  - `app/controller/Goods.php`：`import()`、`downloadTemplate()`
  - 下载模板：含表头的 Excel 文件（商品名称、商品条码、单位、箱规、进货价、零售价、库存数量、商品分类、stock_min、stock_max）
  - 上传解析：使用 PhpSpreadsheet 读取，校验条码唯一性、必填字段非空检查、跳过空行
  - 导入结果展示：成功条数、失败条数及失败原因

- [x] Task 17: 实现批量修改分类
  - `app/controller/Goods.php`：`batchCate()`
  - 勾选多个商品 → 点击"批量改分类" → 弹窗选择目标分类 → 批量更新 goods.cate 字段

## 第六阶段：进货管理模块

- [x] Task 18: 实现手动新建进货单
  - 创建 `app/controller/Purchase.php`：`add()`、`doAdd()`、`searchGoods()`
  - 创建 `view/purchase/add.html`：选择供货商（lay-search 下拉）、搜索商品添加、明细表格（箱规/箱数/散件可编辑）
  - 明细金额公式：`purchase_price × (box_spec × box_count + piece_count)`
  - 进货单号生成规则：`JH` + Ymd + 3位序号（同日自增）
  - 保存事务：写入 purchase → 写入 purchase_detail 多条 → 累加 goods.stock → 更新 goods.box_spec
  - 搜索结果项使用 `<button>` 元素

- [x] Task 19: 实现进货历史查询与明细
  - `app/controller/Purchase.php`：`index()`、`detail()`
  - 创建 `view/purchase/index.html`：Layui table（时间范围、供货商下拉、单号搜索）
  - 创建 `view/purchase/detail.html`：展示进货主表信息 + 明细列表（含箱规/箱数/散件计算）

- [x] Task 20: 实现进货单 Excel 单行导入
  - `app/controller/Purchase.php`：`import()`、`downloadTemplate()`
  - 下载模板：供货商名称、商品条码、商品名称、单位、进货价、零售价、箱规、箱数、散件数量
  - 上传解析：校验供货商名称有效性、商品条码有效性
  - 无效数据跳过并记录原因，有效数据按供货商分组生成进货单并更新库存
  - 展示导入结果（成功张数、商品种类数、失败条数、详情）

- [x] Task 21: 实现多 Sheet 复杂格式进货单导入（importSheets）
  - `app/controller/Purchase.php`：`importSheets()`
  - 解析含多个 Sheet 的 Excel 文件
  - 自动识别表头行（含"货号"和"商品名称"）、日期行（含"日期："，支持"xxxx年xx月xx日"中文格式）
  - 自动检测列偏移，按 Sheet 分单导入
  - 自动创建不存在的商品，固定供货商"武汉海聘电子商务有限公司"
  - Sheet 名称作为备注，展示导入结果弹窗（张数、商品种类数、日志）

- [x] Task 22: 实现进货数据导出
  - `app/controller/Purchase.php`：`export()`
  - 导出为 XLSX，含进货主表 + 明细信息

## 第七阶段：库存管理与预警模块

- [x] Task 23: 实现库存总览
  - 创建 `app/controller/Stock.php`：`index()`、`updateThreshold()`
  - 创建 `view/stock/index.html`
  - Layui table 展示所有商品：名称、条码、分类、库存、stock_min、stock_max
  - 库存低于 stock_min 行添加 CSS class `row-stock-low`（红色背景），高于 stock_max 行添加 `row-stock-high`（黄色背景）
  - 内联编辑 stock_min / stock_max（Layui table edit），通过 `updateThreshold` 接口即时保存
  - 搜索：名称/条码/分类筛选

- [x] Task 24: 实现库存预警页
  - `app/controller/Stock.php`：`warning()`、`warningExport()`
  - 创建 `view/stock/warning.html`
  - 预警类型筛选按钮：全部 / 低于最小库存 / 高于最大库存（通过 URL query 参数 type 切换）
  - 表格展示预警商品，全部添加 CSS class `row-warning`（红色背景）
  - 每行操作按钮：「编辑阈值」（弹窗修改 stock_min/stock_max）

- [x] Task 25: 实现库存流水明细查询
  - `app/controller/Stock.php`：`detail()`
  - 按 barcode 查询所有进货流入和销售流出记录
  - 弹窗展示：时间、类型（进货/销售，不同颜色）、变动数量（+/-前缀）、变动后库存、关联单号

- [x] Task 26: 实现库存数据导出
  - `app/controller/Stock.php`：`export()`

## 第八阶段：收银台核心模块

- [x] Task 27: 实现收银台主界面
  - 创建 `app/controller/Cashier.php`：`index()`、`searchGoods()`、`searchMember()`
  - 创建 `view/cashier/index.html`
  - 左侧搜索/扫码区（条码输入框 + 商品搜索结果列表）+ 会员搜索区
  - 右侧购物车表格（Layui table）
  - 购物车展示：商品名称、单价、数量（+/- 按钮）、小计、删除
  - 底部汇总：商品总数、原价总金额、折扣金额、实付金额
  - 支付方式 Radio：现金 / 会员余额
  - 搜索结果项使用 `<button>` 元素

- [x] Task 28: 实现购物车操作
  - 扫码加购：输入框回车 → 调用 searchGoods → 找到商品加入购物车（纯前端数组存储）
  - 已存在商品数量 +1（不超过库存）
  - 选择会员时重新计算折扣金额：`pay_amount = total_amount × member_cate.discount`

- [x] Task 29: 实现结算下单
  - `app/controller/Cashier.php`：`doCheckout()`
  - 参数：购物车 JSON 数组、支付方式、会员ID
  - 服务端二次校验：库存充足性（逐商品对比 goods.stock）
  - 会员余额支付：校验 member.balance ≥ pay_amount
  - 订单号生成：`DD` + Ymd + 三位流水号
  - 事务写入：order + order_detail → 逐商品扣减 goods.stock → 余额支付则扣减 member.balance
  - 结算成功后弹窗显示订单号，清空购物车

## 第九阶段：订单管理模块

- [x] Task 30: 实现订单列表与明细
  - 创建 `app/controller/Order.php`：`index()`、`detail()`
  - 创建 `view/order/index.html`：筛选（时间范围 laydate、订单号）、列表
  - 列表展示：订单号、原价、折扣、实付、支付方式、会员名称、时间
  - 收银员仅看本人订单（role_id=3 时自动过滤 operator_id）
  - 创建 `view/order/detail.html`：订单主信息 + 明细列表（含进货价用于毛利分析）

- [x] Task 31: 实现订单数据导出
  - `app/controller/Order.php`：`export()`

## 第十阶段：会员管理模块

- [x] Task 32: 实现会员分类管理
  - `app/controller/Member.php`：`cateList()`、`cateAdd()`、`cateEdit()`、`cateDelete()`
  - 创建 `view/member/cate_list.html`：列表 + 新增/编辑弹窗（名称、折扣率）
  - 删除校验：有关联会员的分类禁止删除

- [x] Task 33: 实现会员档案管理
  - `app/controller/Member.php`：`index()`、`add()`、`edit()`、`delete()`
  - 创建 `view/member/index.html`：搜索（姓名/手机号）、列表
  - 弹窗表单：姓名、手机号、分类下拉、备注
  - 手机号唯一性校验
  - 删除校验：有订单记录的会员禁止删除

- [x] Task 34: 实现会员充值
  - `app/controller/Member.php`：`recharge()`、`doRecharge()`
  - 创建 `view/member/recharge.html`：搜索会员（前端过滤）、充值金额输入、确认按钮
  - 充值逻辑：查询当前 balance → 写入 member_recharge（before_balance/after_balance）→ 更新 member.balance
  - 搜索结果项使用 `<button>` 元素，输入框 `autocomplete="off"`

- [x] Task 35: 实现充值记录查询
  - `app/controller/Member.php`：`rechargeLog()`
  - 创建 `view/member/recharge_log.html`：筛选（会员搜索、时间范围）、列表（会员名、金额、充值前/后余额、时间）

- [x] Task 36: 实现会员数据导出
  - `app/controller/Member.php`：`export()`

## 第十一阶段：数据统计模块

- [x] Task 37: 实现统计仪表盘
  - 完善 `app/controller/Index.php`：`index()` 查询统计数据
  - 完善 `view/index/index.html`
  - 数字卡片：今日营收、今日订单、累计营收、累计订单
  - 基础数据：商品总数、会员总数、供货商总数
  - ECharts 近15日销售/进货趋势图（双折线，smooth: true）
  - ECharts 商品销量 TOP10（横向柱状图，inverse: true）
  - 窗口 resize 时图表自适应

## 第十二阶段：Excel 导入导出通用能力

- [x] Task 38: 引入 PhpSpreadsheet 5.7
  - `composer require phpoffice/phpspreadsheet`
  - 各控制器内直接使用 PhpSpreadsheet 进行导入导出

- [x] Task 39: 实现各模块导出接口（汇总串联）
  - 商品导出(Goods/export)、供货商导出(Supplier/export)、进货单导出(Purchase/export)、订单导出(Order/export)、会员导出(Member/export)、库存导出(Stock/export)、预警导出(Stock/warningExport)
  - 所有导出文件 XLSX 格式正确，时间戳转为日期格式，金额保留两位小数

## 第十三阶段：Web Interface Guidelines 合规

- [x] Task 40: 语义化标签优化
  - 所有 Layui table 工具栏 `<a lay-event>` 替换为 `<button lay-event>`
  - 搜索结果列表项从 `<div>` 改为 `<button type="button">`
  - 登录按钮添加 loading 状态（"登录中…"）

- [x] Task 41: 焦点状态与无障碍
  - `layout.html` 添加 `:focus-visible` 全局样式（outline: 2px solid #009688）
  - 输入框 focus 状态添加 box-shadow 增强
  - 添加 `touch-action: manipulation` 和 `-webkit-tap-highlight-color`

- [x] Task 42: 表单优化
  - 手机号输入框 `type="text"` → `type="tel"`
  - 登录页 `autocomplete="username"` / `autocomplete="current-password"`
  - 所有 placeholder 以 `…` 结尾
  - 搜索框添加 `<label>`（`layui-hide` 隐藏但可被屏幕阅读器识别）
  - 按钮添加 `aria-label`（图标+文字按钮）

- [x] Task 43: 库存预警 CSS 类化
  - 内联样式 `style="background-color:..."` 替换为 CSS class（`row-stock-low`、`row-stock-high`、`row-warning`）

## 第十四阶段：联调测试与收尾

- [x] Task 44: 全流程业务联调
  - 使用 admin/admin123 登录
  - 测试完整流程：创建供货商 → 创建商品 → 创建进货单 → 查看库存 → 收银台结算 → 查看订单 → 创建会员 → 会员充值 → 会员消费 → 查看统计
  - 切换店长/收银员角色验证权限边界

- [x] Task 45: 创建 PRD 文档
  - 基于实际代码实现整理 `PRD.md`，含完整功能模块需求、数据库字典、系统架构与技术栈、业务流程

- [x] Task 46: 创建部署说明
  - 项目根目录 `README.md`（部署步骤、PHP 版本要求、扩展要求、启动命令、默认账号密码）

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
| Task 16 | Task 13 |
| Task 17 | Task 13 |
| Task 18 | Task 13, Task 12 |
| Task 19 | Task 18 |
| Task 20 | Task 18 |
| Task 21 | Task 18 |
| Task 22 | Task 19 |
| Task 23 | Task 13, Task 18 |
| Task 24 | Task 23 |
| Task 25 | Task 23 |
| Task 26 | Task 23 |
| Task 27 | Task 7, Task 13 |
| Task 28 | Task 27 |
| Task 29 | Task 28 |
| Task 30 | Task 29 |
| Task 31 | Task 30 |
| Task 32 | Task 7 |
| Task 33 | Task 32 |
| Task 34 | Task 33 |
| Task 35 | Task 34 |
| Task 36 | Task 33 |
| Task 37 | Task 19, Task 30 |
| Task 38 | 无 |
| Task 39 | 各模块 Task |
| Task 40-43 | 所有视图 Task |
| Task 44 | 所有模块 Task 完成 |
| Task 45 | 全部完成 |
| Task 46 | 全部完成 |