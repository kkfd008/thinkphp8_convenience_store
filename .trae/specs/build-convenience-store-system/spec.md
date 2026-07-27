# 便利店零售管理系统 Spec

## Why
构建一套完整的便利店零售管理系统，覆盖供货商-进货-商品-库存-收银-订单-会员-统计全流程，适配社区便利店、小型超市等线下零售场景，采用 PHP 8 + ThinkPHP 8 + SQLite3/MySQL 双驱动 + Layui 2.9.10 技术栈实现轻量部署。

## What Changes
- 从零创建 ThinkPHP 8 项目骨架，配置 SQLite3/MySQL 双数据库驱动（通过 `.env` 中 `DB_DRIVER` 切换）
- 创建 13 张业务数据表（admin_user、role、auth_rule、supplier、goods_cate、goods、purchase、purchase_detail、order、order_detail、member_cate、member、member_recharge）
- 实现登录认证与三级角色权限管控（超级管理员/店长/收银员），Auth 中间件细粒度权限校验
- 实现供货商管理模块（CRUD、启用/禁用、关联校验、Excel 导入/导出、模板下载）
- 实现商品管理模块（CRUD、条码随机生成+唯一性校验、分类管理含状态切换、扫码查询、Excel 批量导入/导出、批量修改分类）
- 实现进货管理模块（手动新建、单行 Excel 导入、多 Sheet 复杂格式 Excel 导入、库存联动、历史查询、明细查看、导出）
- 实现库存管理与预警模块（总览、预警筛选、阈值编辑、库存流水明细查询、数据导出）
- 实现收银台模块（扫码加购、购物车编辑、会员折扣、现金/余额支付、结算下单）
- 实现订单管理模块（列表查询、明细查看、角色数据隔离、条件筛选、导出）
- 实现会员管理模块（分类管理、档案管理、充值、充值记录追溯、导出）
- 实现数据统计模块（营收/订单统计、ECharts 趋势图、销量 TOP10）
- 实现 Excel 导入导出通用能力（PhpSpreadsheet 5.7 封装）
- **Web Interface Guidelines 合规**：所有视图模板已通过无障碍性、语义化标签、focus-visible 焦点样式、表单类型优化等检查

## Impact
- Affected specs: 全部新建，无已有功能影响
- Affected code: 整个 `/workspace/` 目录，含 10 个控制器（Auth、Cashier、Goods、Index、Login、Member、Order、Purchase、Stock、Supplier）、87 条路由、21 个视图模板
- **BREAKING**: 无（全新项目）

---

## ADDED Requirements

### Requirement: 项目基础架构
系统 SHALL 基于 ThinkPHP 8 + SQLite3/MySQL 双驱动 + Layui 2.9.10 构建，PHP ≥ 8.0，通过 `.env` 中 `DB_DRIVER` 配置切换数据库类型。

#### Scenario: SQLite 零依赖部署
- **WHEN** `.env` 配置 `DB_DRIVER=sqlite`
- **THEN** 无需安装 MySQL 等外部数据库服务，SQLite 单文件自动工作

#### Scenario: MySQL 生产环境切换
- **WHEN** `.env` 配置 `DB_DRIVER=mysql` 并填写 MySQL 连接参数
- **THEN** 系统无缝切换至 MySQL，ThinkPHP 查询构造器屏蔽数据库差异

#### Scenario: 视图模板常量
- **WHEN** 模板中引用 `{__LAYUI__}` 或 `{__STATIC__}`
- **THEN** 正确解析为对应静态资源路径

---

### Requirement: 登录认证与权限中间件
系统 SHALL 提供管理员登录/退出功能，密码使用 MD5 加密，Session 管理登录态。Auth 中间件（`app/middleware/Auth.php`）校验所有后台请求，未登录跳转登录页，无权限返回 403。

#### Scenario: 管理员登录成功
- **WHEN** 输入正确的账号和密码
- **THEN** 系统写入 Session，跳转至首页仪表盘，按钮显示"登录中…"加载态

#### Scenario: 未登录访问后台
- **WHEN** 直接访问后台页面 URL
- **THEN** 系统拦截并跳转至登录页

#### Scenario: 收银员访问商品管理
- **WHEN** 收银员角色尝试访问商品新增/编辑页面
- **THEN** 系统拒绝访问，提示无权限

#### Scenario: 登录表单无障碍
- **WHEN** 访问登录页
- **THEN** 账号输入框 `autocomplete="username"`，密码框 `autocomplete="current-password"`，placeholder 以 `…` 结尾

---

### Requirement: 权限规则与角色管理
系统 SHALL 支持超级管理员配置权限规则（无限级菜单树，含 title、name、icon、sort 字段）和角色（绑定权限集合），以及管理员账号的增删改查。共 22 条权限规则构成完整菜单树。

#### Scenario: 新增权限规则
- **WHEN** 超级管理员在权限规则页新增子菜单
- **THEN** 菜单树结构正确更新，角色可勾选新增的权限

#### Scenario: 角色分配权限
- **WHEN** 超级管理员编辑角色，勾选/取消权限树复选框
- **THEN** 该角色下所有管理员登录后菜单即时生效，rules 以逗号分隔存储

#### Scenario: 权限规则树形展示
- **WHEN** 访问权限规则列表页（`view/auth/rule_list.html`）
- **THEN** 树形表格展示无限级菜单层级，含缩进和 ├─ 前缀

---

### Requirement: 供货商管理
系统 SHALL 支持供货商的新增、编辑、删除、列表查询、状态启用/禁用、Excel 批量导入、Excel 数据导出、导入模板下载。已关联进货单的供货商禁止删除，仅可禁用。

#### Scenario: 删除有关联的供货商
- **WHEN** 尝试删除已有关联进货记录的供货商
- **THEN** 系统提示"该供货商已关联进货单，禁止删除"，操作被拒绝

#### Scenario: 禁用供货商
- **WHEN** 将供货商状态改为禁用
- **THEN** 该供货商在进货单新建页不再可选

#### Scenario: Excel 批量导入供货商
- **WHEN** 上传符合模板格式的 Excel 文件（名称、联系人、电话、地址、备注、状态）
- **THEN** 系统解析数据，批量写入 supplier 表，展示导入结果

---

### Requirement: 商品管理
系统 SHALL 支持商品增删改查，条码手工填写或随机生成 13 位数字条码（`genBarcode` 接口），条码唯一性前端+后端双重校验（`checkBarcode` 接口）。所有业务关联以 barcode 为准。已关联进货/订单的商品禁止删除，且不可修改条码。stock_min/stock_max 可为空（NULL）。

#### Scenario: 随机生成条码
- **WHEN** 新增商品时点击"随机生成"按钮
- **THEN** 条码输入框自动填入 13 位随机数字，且不与已有条码冲突

#### Scenario: 修改已关联进货的商品条码
- **WHEN** 编辑已有关联进货记录的商品，尝试修改条码
- **THEN** 条码字段置灰 `readonly`，不可编辑

#### Scenario: Excel 批量导入商品
- **WHEN** 上传符合模板格式的 Excel 文件
- **THEN** 系统解析数据，校验条码唯一性和必填字段，去重跳过空行，展示导入成功/失败数量及失败原因

#### Scenario: 批量修改分类
- **WHEN** 勾选多个商品后点击"批量改分类"按钮，选择目标分类
- **THEN** 选中的商品分类字段批量更新为所选分类（`batchCate` 接口）

---

### Requirement: 商品分类管理
系统 SHALL 支持商品分类的独立管理（`goods_cate` 表），含分类名称和显示/隐藏状态切换。分类数据独立于商品，商品通过 `cate` 字段（VARCHAR）存储分类名称。

#### Scenario: 切换分类显示状态
- **WHEN** 点击分类列表中的"隐藏"或"显示"按钮
- **THEN** 对应分类 `status` 字段更新（1=显示/0=隐藏），通过 `cateToggle` 接口实现

---

### Requirement: 进货管理
系统 SHALL 支持手动新建进货单（选择供货商、添加明细、箱规/箱数/散件模式），进货单号自动生成（JH+Ymd+三位流水号），保存后自动累加商品库存并更新 goods.box_spec，不可删除。支持 Excel 单行导入和多 Sheet 复杂格式批量导入。

#### Scenario: 新建进货单并保存
- **WHEN** 选择供货商、填写多条进货明细（含箱规、箱数、散件）后提交
- **THEN** 进货主表（purchase）写入，进货明细表（purchase_detail）写入多条记录，对应商品 stock 自动累加（stock += box_spec × box_count + piece_count），goods.box_spec 同步更新

#### Scenario: Excel 单行导入进货单
- **WHEN** 上传进货单 Excel（供货商名称、商品条码、商品名称、单位、进货价、零售价、箱规、箱数、散件数量）
- **THEN** 系统校验商品条码和供货商有效性，无效跳过并记录，有效数据按供货商分组生成进货单并更新库存

#### Scenario: 多 Sheet 复杂格式导入（importSheets）
- **WHEN** 上传含多个 Sheet 的 Excel 文件，每个 Sheet 包含表头行（含"货号"和"商品名称"）、日期行（含"日期："，支持"xxxx年xx月xx日"中文格式）
- **THEN** 系统自动解析表头偏移列，检测日期行，按 Sheet 分单导入，自动创建不存在的商品，固定供货商"武汉海聘电子商务有限公司"，Sheet 名称作为备注，展示导入结果（张数、商品种类数、日志）

#### Scenario: 进货历史查询
- **WHEN** 访问进货列表页，按进货单号/供货商/时间范围筛选
- **THEN** 展示进货主表列表，点击"查看明细"可查看进货明细（含箱规/箱数/散件/金额计算）

---

### Requirement: 库存管理与预警
系统 SHALL 提供库存总览（标红低于 stock_min、标黄高于 stock_max）和库存预警页（按预警类型筛选：全部/低于最小库存/高于最大库存），支持阈值编辑和库存流水明细查询。

#### Scenario: 库存低于最小预警值
- **WHEN** 某商品 stock_min > 0 且 stock < stock_min
- **THEN** 库存总览页该行添加 CSS class `row-stock-low`（红色背景），预警页展示该商品

#### Scenario: 库存高于最大预警值
- **WHEN** 某商品 stock_max > 0 且 stock > stock_max
- **THEN** 库存总览页该行添加 CSS class `row-stock-high`（黄色背景），预警页展示该商品

#### Scenario: 库存阈值内联编辑
- **WHEN** 在库存总览页点击 stock_min 或 stock_max 单元格
- **THEN** 单元格变为可编辑输入框（Layui table edit），修改后通过 `updateThreshold` 接口即时保存

#### Scenario: 库存流水明细查询
- **WHEN** 点击某商品的"库存明细"按钮
- **THEN** 弹窗展示该商品所有进货流入和销售流出记录（时间、类型、变动数量、变动后库存、关联单号），通过 `stock/detail` 接口按 barcode 查询

---

### Requirement: 收银台
系统 SHALL 提供收银台界面，支持扫码/搜索加购、购物车编辑、会员搜索与选择（自动关联会员分类折扣）、现金/会员余额两种支付方式、结算下单自动扣减库存。

#### Scenario: 扫码加购
- **WHEN** 收银员使用扫码枪扫描商品条码或在搜索框输入后回车
- **THEN** 对应商品自动加入购物车，数量默认为 1，已存在则数量 +1

#### Scenario: 会员余额支付
- **WHEN** 选择会员且选择"会员余额"支付方式，会员余额足够
- **THEN** 结算成功，订单写入，库存扣减，会员 balance 扣减实付金额

#### Scenario: 库存不足禁止结算
- **WHEN** 购物车中某商品数量超过当前库存
- **THEN** 系统提示"库存不足，当前库存：XX"，禁止提交结算

#### Scenario: 购物车按钮无障碍
- **WHEN** 购物车商品项渲染为搜索结果
- **THEN** 搜索结果项使用 `<button>` 元素（非 `<div>`），具有 `type="button"` 属性

---

### Requirement: 订单管理
系统 SHALL 提供订单列表查询、明细查看、条件筛选（时间、订单号、会员）、角色数据隔离（收银员 role_id=3 仅看本人订单）、数据导出。订单生成后永久留存，不可修改/删除。订单号格式：DD+Ymd+三位流水号。

#### Scenario: 收银后查看订单
- **WHEN** 收银结算完成后，在订单列表页按时间筛选
- **THEN** 新生成的订单出现在列表中，可查看完整明细（含商品进货价用于毛利分析）

#### Scenario: 收银员数据隔离
- **WHEN** 收银员（role_id=3）访问订单列表
- **THEN** 仅展示 operator_id 等于当前用户 ID 的订单

#### Scenario: 订单明细查看
- **WHEN** 点击订单"查看明细"按钮
- **THEN** 跳转订单详情页，展示订单主信息（订单号、支付方式、原价/折扣/实付、会员名、时间）+ 明细列表（条码、商品名、进价、售价、数量、成本、销售金额）

---

### Requirement: 会员管理
系统 SHALL 支持会员分类管理（member_cate 表，含折扣率配置）、会员档案增删改查、会员充值（记录前后余额）、充值记录查询、数据导出。手机号全局唯一，会员继承分类折扣。

#### Scenario: 会员充值
- **WHEN** 为会员充值指定金额
- **THEN** member.balance 增加充值金额，member_recharge 记录充值前/后余额及操作员

#### Scenario: 有交易记录会员删除
- **WHEN** 尝试删除有订单记录的会员
- **THEN** 系统提示"该会员存在交易记录，禁止删除"

#### Scenario: 有会员关联的分类删除
- **WHEN** 尝试删除已有关联会员的分类
- **THEN** 系统拒绝删除并提示

#### Scenario: 会员充值页无障碍
- **WHEN** 搜索会员时展示搜索结果
- **THEN** 搜索结果项使用 `<button>` 元素，输入框 `autocomplete="off"` 避免密码管理器干扰

---

### Requirement: 数据统计
系统 SHALL 在首页仪表盘展示今日/累计营收与订单数、商品/会员/供货商基础数量、近15日销售与进货趋势图（ECharts 双折线）、商品销量 TOP10 横向柱状图。

#### Scenario: 首页数据刷新
- **WHEN** 访问首页仪表盘
- **THEN** 实时查询展示最新统计数据，ECharts 图表正常渲染，窗口 resize 时图表自适应

---

### Requirement: Excel 导入导出
系统 SHALL 支持商品、供货商、进货单、订单、会员、库存、预警全数据导出为 XLSX 格式，时间戳自动转为日期格式，金额保留两位小数。支持商品、供货商、进货单（单行+多Sheet）Excel 导入。

#### Scenario: 导出商品列表
- **WHEN** 在商品列表页点击"导出"按钮
- **THEN** 浏览器下载 XLSX 文件，内容与当前筛选结果一致，时间列为日期格式

#### Scenario: 下载导入模板
- **WHEN** 在商品/供货商/进货管理页点击"下载模板"按钮
- **THEN** 浏览器下载对应模块的 Excel 模板文件，含表头行

---

### Requirement: Web Interface Guidelines 合规
系统所有视图模板 SHALL 符合 Web Interface Guidelines 规范，包括语义化 HTML、无障碍性、焦点状态、表单优化等方面。

#### Scenario: 语义化按钮
- **WHEN** 表格工具栏渲染操作按钮（编辑/删除/查看明细等）
- **THEN** 使用 `<button lay-event="...">` 而非 `<a lay-event="...">`

#### Scenario: 焦点可见
- **WHEN** 用户通过 Tab 键导航至任意交互元素
- **THEN** 元素显示 `outline: 2px solid #009688` 焦点环，使用 `:focus-visible` 选择器

#### Scenario: 表单输入类型
- **WHEN** 表单包含手机号输入框
- **THEN** 使用 `type="tel"` 而非 `type="text"`

#### Scenario: 占位符文本
- **WHEN** 输入框使用 placeholder 属性
- **THEN** 占位符文本以 `…` 结尾，如"请输入账号…"

#### Scenario: 触摸优化
- **WHEN** 移动端访问系统
- **THEN** `<html>` 元素设置 `touch-action: manipulation`，`-webkit-tap-highlight-color: transparent`