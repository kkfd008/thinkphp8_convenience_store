# 便利店零售管理系统（Web版）实施计划

## 项目信息
- **技术栈**：PHP 8+ / ThinkPHP 8 / SQLite3 / Layui 2.9.10 / ECharts
- **目标目录**：`d:\dev\project\newbld`
- **最终目标**：从零构建完整可运行的便利店零售管理系统

---

## 架构约定

### 后端约定
- ThinkPHP 8 多应用模式，单应用 `app/`
- 控制器命名：`app/controller/` 下，每个模块一个控制器文件（如 `Goods.php`、`Supplier.php`）
- 数据验证：使用 ThinkPHP 内置验证器 `app/validate/`
- 数据库操作：使用 ThinkPHP ORM（Db 类或 Model）
- 公共逻辑抽取到 `app/common.php` 或 `app/service/`
- Excel 导入导出：使用 PhpSpreadsheet（composer require）
- 登录态：Session 驱动
- 权限校验：中间件 `app/middleware/Auth.php`
- 密码加密：MD5（按需求文档约定）

### 前端约定
- Layui 2.9.10 全套组件（table、form、laydate、upload、element、tree 等）
- 所有页面继承公共布局模板 `view/layout.html`
- 页面放在 `view/模块名/方法名.html`，如 `view/goods/index.html`
- AJAX 统一返回 JSON 格式 `{code: 0, msg: '', data: {}}`
- ECharts CDN 引入（或本地 `public/static/echarts/echarts.min.js`）
- 响应式适配：Layui 自带响应式能力

### 目录结构
```
d:\dev\project\newbld\
├── app/
│   ├── controller/       # 控制器
│   │   ├── Index.php     # 首页/统计
│   │   ├── Login.php     # 登录
│   │   ├── Auth.php      # 权限管理
│   │   ├── Supplier.php  # 供货商
│   │   ├── Goods.php     # 商品
│   │   ├── Purchase.php  # 进货
│   │   ├── Stock.php     # 库存
│   │   ├── Cashier.php   # 收银台
│   │   ├── Order.php     # 订单
│   │   ├── Member.php    # 会员
│   │   └── Export.php    # 导出
│   ├── model/            # 模型
│   ├── validate/         # 验证器
│   ├── middleware/       # 中间件
│   │   └── Auth.php
│   └── common.php        # 公共函数
├── config/
│   ├── app.php
│   ├── database.php
│   ├── view.php
│   └── middleware.php
├── database/
│   ├── shop.db           # SQLite 数据库文件（自动生成）
│   └── schema.sql        # 建表 SQL
├── public/
│   └── static/
│       ├── layui/        # Layui 2.9.10
│       └── echarts/      # ECharts
├── route/
│   └── app.php           # 路由
├── view/
│   ├── layout.html       # 公共布局
│   ├── login/
│   │   └── index.html
│   ├── index/
│   │   └── index.html    # 统计仪表盘
│   ├── auth/             # 权限管理相关页面
│   ├── supplier/         # 供货商相关页面
│   ├── goods/            # 商品相关页面
│   ├── purchase/         # 进货相关页面
│   ├── stock/            # 库存相关页面
│   ├── cashier/          # 收银台
│   ├── order/            # 订单相关页面
│   └── member/           # 会员相关页面
├── vendor/
├── .env                  # 环境配置
├── composer.json
└── public/
    └── index.php         # 入口
```

---

## 数据库完整设计

需求文档中 `purchase` 表之后的内容被截断，以下基于业务逻辑合理推导出完整设计。

### 1. admin_user（管理员表）
| 字段 | 类型 | 主键 | 自增 | 可为空 | 默认值 | 说明 |
|------|------|------|------|--------|--------|------|
| id | INTEGER | 是 | 是 | 否 | - | 管理员唯一ID |
| username | VARCHAR(50) | 否 | 否 | 否 | - | 登录账号 |
| password | VARCHAR(32) | 否 | 否 | 否 | - | MD5加密密码 |
| role_id | INTEGER | 否 | 否 | 是 | 0 | 关联角色ID |
| create_time | INTEGER | 否 | 否 | 是 | 0 | 创建时间戳 |

### 2. role（角色表）
| 字段 | 类型 | 主键 | 自增 | 可为空 | 默认值 | 说明 |
|------|------|------|------|--------|--------|------|
| id | INTEGER | 是 | 是 | 否 | - | 角色ID |
| name | VARCHAR(50) | 否 | 否 | 否 | - | 角色名称 |
| rules | TEXT | 否 | 否 | 是 | '' | 权限规则ID集合，逗号分隔 |

### 3. auth_rule（权限规则表）
| 字段 | 类型 | 主键 | 自增 | 可为空 | 默认值 | 说明 |
|------|------|------|------|--------|--------|------|
| id | INTEGER | 是 | 是 | 否 | - | 权限规则ID |
| pid | INTEGER | 否 | 否 | 是 | 0 | 父级规则ID，0为顶级菜单 |
| title | VARCHAR(50) | 否 | 否 | 否 | - | 菜单/权限名称 |
| name | VARCHAR(100) | 否 | 否 | 否 | - | 控制器+方法标识，用于权限校验 |
| icon | VARCHAR(50) | 否 | 否 | 是 | '' | Layui图标class |
| sort | INTEGER | 否 | 否 | 是 | 0 | 排序 |

### 4. supplier（供货商表）
| 字段 | 类型 | 主键 | 自增 | 可为空 | 默认值 | 说明 |
|------|------|------|------|--------|--------|------|
| id | INTEGER | 是 | 是 | 否 | - | 供货商ID |
| name | VARCHAR(100) | 否 | 否 | 否 | - | 供货商名称 |
| contact | VARCHAR(50) | 否 | 否 | 是 | '' | 联系人 |
| phone | VARCHAR(20) | 否 | 否 | 是 | '' | 联系电话 |
| address | VARCHAR(255) | 否 | 否 | 是 | '' | 详细地址 |
| remark | TEXT | 否 | 否 | 是 | '' | 备注信息 |
| status | TINYINT | 否 | 否 | 是 | 1 | 状态：1启用 0禁用 |
| create_time | INTEGER | 否 | 否 | 是 | 0 | 创建时间戳 |

### 5. goods（商品表）
| 字段 | 类型 | 主键 | 自增 | 可为空 | 默认值 | 说明 |
|------|------|------|------|--------|--------|------|
| id | INTEGER | 是 | 是 | 否 | - | 商品ID |
| name | VARCHAR(100) | 否 | 否 | 否 | - | 商品名称 |
| barcode | VARCHAR(50) | 否 | 否 | 否 | - | 商品条码，全局唯一 |
| purchase_price | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 进货价 |
| retail_price | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 零售价 |
| stock | INTEGER | 否 | 否 | 是 | 0 | 当前库存 |
| stock_min | INTEGER | 否 | 否 | 是 | NULL | 最小库存预警值 |
| stock_max | INTEGER | 否 | 否 | 是 | NULL | 最大库存预警值 |
| cate | VARCHAR(50) | 否 | 否 | 是 | '' | 商品分类 |
| create_time | INTEGER | 否 | 否 | 是 | 0 | 创建时间戳 |

### 6. purchase（进货主表）—— 基于文档补充完整
| 字段 | 类型 | 主键 | 自增 | 可为空 | 默认值 | 说明 |
|------|------|------|------|--------|--------|------|
| id | INTEGER | 是 | 是 | 否 | - | 进货单ID |
| purchase_no | VARCHAR(30) | 否 | 否 | 否 | - | 进货单号，系统自动生成，唯一 |
| supplier_id | INTEGER | 否 | 否 | 否 | 0 | 供货商ID |
| total_amount | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 供货总金额 |
| total_goods_num | INTEGER | 否 | 否 | 是 | 0 | 总商品数量 |
| operator_id | INTEGER | 否 | 否 | 是 | 0 | 操作员ID |
| remark | TEXT | 否 | 否 | 是 | '' | 备注 |
| create_time | INTEGER | 否 | 否 | 是 | 0 | 进货时间戳 |

### 7. purchase_detail（进货明细表）—— 基于业务推导
| 字段 | 类型 | 主键 | 自增 | 可为空 | 默认值 | 说明 |
|------|------|------|------|--------|--------|------|
| id | INTEGER | 是 | 是 | 否 | - | 明细ID |
| purchase_id | INTEGER | 否 | 否 | 否 | 0 | 进货单ID |
| barcode | VARCHAR(50) | 否 | 否 | 否 | - | 商品条码，关联goods.barcode |
| goods_name | VARCHAR(100) | 否 | 否 | 否 | - | 进货时商品名称（快照） |
| purchase_price | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 进货价 |
| retail_price | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 零售价 |
| box_spec | INTEGER | 否 | 否 | 是 | 0 | 箱规（每箱件数） |
| box_count | INTEGER | 否 | 否 | 是 | 0 | 箱数 |
| piece_count | INTEGER | 否 | 否 | 是 | 0 | 散件数量 |
| total_amount | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 明细金额 = purchase_price × (box_spec×box_count + piece_count) |
| create_time | INTEGER | 否 | 否 | 是 | 0 | 创建时间戳 |

### 8. `order`（订单主表）—— 基于业务推导
| 字段 | 类型 | 主键 | 自增 | 可为空 | 默认值 | 说明 |
|------|------|------|------|--------|--------|------|
| id | INTEGER | 是 | 是 | 否 | - | 订单ID |
| order_no | VARCHAR(30) | 否 | 否 | 否 | - | 订单号，系统自动生成 |
| total_amount | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 原价总金额 |
| discount_amount | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 折扣金额 |
| pay_amount | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 实付金额 |
| pay_type | TINYINT | 否 | 否 | 是 | 1 | 支付方式：1现金 2会员余额 |
| member_id | INTEGER | 否 | 否 | 是 | 0 | 会员ID，0表示非会员 |
| operator_id | INTEGER | 否 | 否 | 是 | 0 | 收银员ID |
| remark | VARCHAR(255) | 否 | 否 | 是 | '' | 备注 |
| create_time | INTEGER | 否 | 否 | 是 | 0 | 下单时间戳 |

### 9. order_detail（订单明细表）—— 基于业务推导
| 字段 | 类型 | 主键 | 自增 | 可为空 | 默认值 | 说明 |
|------|------|------|------|--------|--------|------|
| id | INTEGER | 是 | 是 | 否 | - | 明细ID |
| order_id | INTEGER | 否 | 否 | 否 | 0 | 订单ID |
| barcode | VARCHAR(50) | 否 | 否 | 否 | - | 商品条码 |
| goods_name | VARCHAR(100) | 否 | 否 | 否 | - | 商品名称快照 |
| retail_price | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 零售价 |
| quantity | INTEGER | 否 | 否 | 是 | 0 | 数量 |
| total_amount | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 明细金额 |
| create_time | INTEGER | 否 | 否 | 是 | 0 | 创建时间戳 |

### 10. member_cate（会员分类表）—— 基于业务推导
| 字段 | 类型 | 主键 | 自增 | 可为空 | 默认值 | 说明 |
|------|------|------|------|--------|--------|------|
| id | INTEGER | 是 | 是 | 否 | - | 分类ID |
| name | VARCHAR(50) | 否 | 否 | 否 | - | 分类名称 |
| discount | DECIMAL(10,2) | 否 | 否 | 是 | 1.00 | 折扣率（如0.95表示95折） |
| create_time | INTEGER | 否 | 否 | 是 | 0 | 创建时间戳 |

### 11. member（会员表）—— 基于业务推导
| 字段 | 类型 | 主键 | 自增 | 可为空 | 默认值 | 说明 |
|------|------|------|------|--------|--------|------|
| id | INTEGER | 是 | 是 | 否 | - | 会员ID |
| name | VARCHAR(50) | 否 | 否 | 否 | - | 会员姓名 |
| phone | VARCHAR(20) | 否 | 否 | 否 | - | 手机号，全局唯一 |
| cate_id | INTEGER | 否 | 否 | 是 | 0 | 会员分类ID |
| balance | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 账户余额 |
| remark | VARCHAR(255) | 否 | 否 | 是 | '' | 备注 |
| create_time | INTEGER | 否 | 否 | 是 | 0 | 创建时间戳 |

### 12. member_recharge（会员充值记录表）—— 基于业务推导
| 字段 | 类型 | 主键 | 自增 | 可为空 | 默认值 | 说明 |
|------|------|------|------|--------|--------|------|
| id | INTEGER | 是 | 是 | 否 | - | 记录ID |
| member_id | INTEGER | 否 | 否 | 否 | 0 | 会员ID |
| amount | DECIMAL(10,2) | 否 | 否 | 否 | 0.00 | 充值金额 |
| before_balance | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 充值前余额 |
| after_balance | DECIMAL(10,2) | 否 | 否 | 是 | 0.00 | 充值后余额 |
| operator_id | INTEGER | 否 | 否 | 是 | 0 | 操作员ID |
| create_time | INTEGER | 否 | 否 | 是 | 0 | 充值时间戳 |

> 共 **12 张表**，其中 admin_user、role、auth_rule、supplier、goods、purchase（6 张）来自需求文档明确定义，purchase_detail、order、order_detail、member_cate、member、member_recharge（6 张）基于业务逻辑合理推导补充。所有金额字段统一 DECIMAL(10,2)，时间字段统一 INTEGER 时间戳。

---

## 实施阶段

---

### 第一阶段：项目初始化与基础设施

#### 步骤 1.1：创建 ThinkPHP 8 项目骨架
- 使用 Composer 创建 ThinkPHP 8 项目
- 确认 PHP ≥ 8.0，开启 `pdo_sqlite` 扩展
- 生成基本目录结构

#### 步骤 1.2：配置 SQLite3 数据库连接
- 修改 `config/database.php`，配置 SQLite 连接
- 数据库文件路径：`database/shop.db`
- 修改 `.env` 配置

#### 步骤 1.3：创建建表 SQL 脚本
- 编写 `database/schema.sql`（全部 12 张表）
- 编写种子数据 SQL（初始角色、权限、管理员账号）
- 执行建表脚本，生成 `shop.db`

#### 步骤 1.4：引入 Layui 2.9.10 与 ECharts
- Layui 2.9.10 放入 `public/static/layui/`
- ECharts 放入 `public/static/echarts/`
- 创建公共布局模板 `view/layout.html`

#### 步骤 1.5：配置基础
- 配置 `config/app.php`（调试、时区等）
- 配置 `config/view.php`（模板引擎、标签替换）
- 配置 `config/middleware.php`（注册权限中间件）
- 创建 `app/BaseController.php`（公共基类）

---

### 第二阶段：权限认证模块

#### 步骤 2.1：登录功能
- 登录页面 `view/login/index.html`
- `app/controller/Login.php`：login()、logout()
- Session 管理登录态

#### 步骤 2.2：权限中间件
- `app/middleware/Auth.php`
- 校验登录态（未登录 → 登录页）
- 校验权限（无权限 → 403 提示）

#### 步骤 2.3：首页仪表盘
- `app/controller/Index.php`：index()
- `view/index/index.html`：数据统计看板
- 左侧菜单动态渲染（根据权限）

---

### 第三阶段：权限配置管理（超级管理员专属）

#### 步骤 3.1：权限规则管理
- 列表 `view/auth/rule.html`：无限级树形展示
- 新增/编辑子权限
- 删除（级联删除子权限）

#### 步骤 3.2：角色管理
- 列表 `view/auth/role.html`
- 新增/编辑角色，分配权限（权限树勾选）
- 删除角色

#### 步骤 3.3：管理员账号管理
- 列表 `view/auth/admin.html`
- 新增/编辑/删除管理员
- 分配角色

---

### 第四阶段：供货商管理模块

#### 步骤 4.1：供货商 CRUD
- 列表页 `view/supplier/index.html`：搜索、状态筛选、分页
- 新增/编辑页 `view/supplier/edit.html`：表单
- 删除逻辑：已关联进货单的禁止删除，仅可禁用
- `app/controller/Supplier.php` + `app/model/Supplier.php`

---

### 第五阶段：商品管理模块

#### 步骤 5.1：商品 CRUD
- 列表页 `view/goods/index.html`：按名称/条码/分类搜索，分页
- 新增/编辑页 `view/goods/edit.html`
- 条码手工填写 + 「随机生成」按钮（前端13位随机数）
- 业务规则：已关联进货/订单的商品禁止删除，且不可修改条码
- `app/controller/Goods.php` + `app/model/Goods.php`

#### 步骤 5.2：商品分类管理
- 简单维护：新增商品时输入/选择分类
- 分类独立维护页面 `view/goods/cate.html`

#### 步骤 5.3：扫码快速查询
- 列表搜索框输入条码 → 回车 → 搜索命中

#### 步骤 5.4：商品 Excel 批量导入
- 下载模板
- 上传 Excel（PhpSpreadsheet 解析）
- 校验：条码唯一、必填字段、去重、跳过空行
- 展示导入结果

---

### 第六阶段：进货管理模块

#### 步骤 6.1：手动新建进货单
- 新建页 `view/purchase/add.html`
- 选择供货商（下拉搜索）
- 动态添加进货明细行（搜索/选择商品 → 填箱规/箱数/散件数 → 自动计算金额）
- 进货单号自动生成（JH + 年月日 + 序号）
- 保存后写入 purchase + purchase_detail，自动累加 goods.stock
- 不可删除，永久留存

#### 步骤 6.2：进货单 Excel 批量导入
- 下载模板
- 上传 Excel，校验商品条码有效性
- 无效跳过并记录，有效的自动入库更新库存
- 展示导入结果

#### 步骤 6.3：进货历史查询
- 列表页 `view/purchase/index.html`
- 按时间/供货商/单号筛选
- 点击查看进货明细 `view/purchase/detail.html`

#### 步骤 6.4：进货数据导出
- 导出为 XLSX 格式

---

### 第七阶段：库存管理与预警模块

#### 步骤 7.1：库存总览
- 列表页 `view/stock/index.html`
- 展示所有商品：名称、条码、分类、库存、stock_min、stock_max
- 库存低于 stock_min 标红，高于 stock_max 标黄
- 快捷编辑预警阈值（内联编辑 stock_min / stock_max）
- 搜索、分类筛选、分页

#### 步骤 7.2：库存预警页
- `view/stock/warning.html`
- 按预警类型筛选：低于最小库存 / 高于最大库存
- 预警商品标红突出显示
- 展示：商品名称、当前库存、预警阈值、条码
- 「编辑预警阈值」快捷按钮
- 「一键补货」：跳转至进货页面预填商品信息

#### 步骤 7.3：库存数据导出
- 导出为 XLSX 格式

---

### 第八阶段：收银台核心模块

#### 步骤 8.1：收银台主界面
- `view/cashier/index.html`
- 左侧：商品扫码/搜索加购区
- 右侧：购物车列表（商品、单价、数量、小计）
- 底部：总金额、折扣、实付金额
- 支付方式：现金 / 会员余额
- 选择会员：搜索手机号/姓名 → 自动匹配折扣

#### 步骤 8.2：购物车操作
- 扫码添加商品（条码输入框自动聚焦，回车加购）
- 手改数量（+/- 按钮）
- 删除购物车行
- 清空购物车

#### 步骤 8.3：结算下单
- 校验：库存不足禁止结算
- 会员余额支付校验：余额不足禁止使用
- 确认结算 → 写入 order + order_detail
- 自动扣减 goods.stock
- 若会员余额支付，扣减 member.balance
- 生成不可修改/删除的正式订单
- 打印小票（预留接口）

---

### 第九阶段：订单管理模块

#### 步骤 9.1：订单列表
- `view/order/index.html`
- 筛选：时间范围、订单号、会员
- 列表：订单号、金额、支付方式、会员、时间、收银员
- 点击查看订单明细 `view/order/detail.html`
- 分页

#### 步骤 9.2：订单数据导出
- 导出为 XLSX 格式

---

### 第十阶段：会员管理模块

#### 步骤 10.1：会员分类管理
- `view/member/cate.html`
- 三级分类增删改查
- 每级设置折扣率

#### 步骤 10.2：会员档案管理
- 列表 `view/member/index.html`：搜索、分页
- 新增/编辑 `view/member/edit.html`
- 手机号全局唯一校验
- 自动继承所属分类折扣
- 删除：有交易记录的会员禁止删除

#### 步骤 10.3：会员充值
- `view/member/recharge.html`
- 选择会员 → 输入金额 → 确认
- 写入 member_recharge 记录（before_balance / after_balance）
- 更新 member.balance

#### 步骤 10.4：充值记录查询
- `view/member/recharge_log.html`
- 按会员/时间筛选
- 追溯资金变动

#### 步骤 10.5：会员数据导出
- 导出为 XLSX 格式

---

### 第十一阶段：数据统计模块

#### 步骤 11.1：统计仪表盘
- `view/index/index.html`
- 数字统计卡片：今日/累计营收、今日/累计订单数
- 系统基础数据：商品数、会员数、供货商数
- ECharts 图表：近15日销售/进货趋势图（双折线）
- ECharts 图表：商品销量 TOP10（横向柱状图）

---

### 第十二阶段：Excel 导入导出通用能力

#### 步骤 12.1：引入 PhpSpreadsheet
- `composer require phpoffice/phpspreadsheet`
- 封装通用导出服务 `app/service/ExportService.php`

#### 步骤 12.2：各模块导出接口
- 商品导出、供货商导出、进货单导出、订单导出、会员导出、库存导出
- 统一 XLSX 格式，时间戳转日期，金额保留两位小数

---

### 第十三阶段：联调测试与收尾

#### 步骤 13.1：功能联调
- 全流程测试：登录 → 供货商 → 商品 → 进货 → 库存 → 收银 → 订单 → 会员 → 统计
- 权限测试：三个角色分别登录验证功能边界

#### 步骤 13.2：Bug 修复
- 根据测试结果修复问题

#### 步骤 13.3：部署文档
- 简要部署说明（PHP 环境要求、目录权限、启动方式）

---

## 实施顺序说明

| 阶段 | 内容 | 依赖 |
|------|------|------|
| 一 | 项目初始化 | 无 |
| 二 | 登录与权限中间件 | 一 |
| 三 | 权限配置管理 | 二 |
| 四 | 供货商管理 | 二 |
| 五 | 商品管理 | 二、四 |
| 六 | 进货管理 | 二、四、五 |
| 七 | 库存管理 | 五、六 |
| 八 | 收银台 | 二、五、十 |
| 九 | 订单管理 | 八 |
| 十 | 会员管理 | 二 |
| 十一 | 数据统计 | 六、九 |
| 十二 | Excel 导入导出 | 五、六 |
| 十三 | 联调测试 | 全部 |

按照一 → 二 → 三、四、十（并行）→ 五 → 六 → 七 → 八 → 九 → 十一 → 十二 → 十三 的顺序执行。
