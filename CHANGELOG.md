# 变更日志 (CHANGELOG)

本文件记录便利店零售管理系统所有主要变更。

格式基于 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.0.0/)。

---

## [Unreleased] — 2026-08-18

### 新增

#### 库存管理模块
- **库存调整**：支持直接库存调整（库存修正/损耗报损/过期处理/其他），自动记录库存流水
- **库存流水表** (`stock_flow`)：独立追踪每次库存变动，记录类型/变动量/余额/关联单号
- **库存流水查询** (`Stock/flow`)：全局流水视图，支持按类型/日期/关键字筛选
- **库存报表** (`Stock/reports`)：KPI 仪表盘（7 项指标）+ 分类/供货商库存金额统计 + 库龄分析
- **批量设置阈值**：勾选商品批量设置库存上下限

#### 盘点管理模块
- **盘点单管理** (`StockCheck`)：创建盘点单 → 录入实盘数量 → 审核 → 自动调整库存
- **盘点模板** (`stock_check_template`)：全盘/按分类/按供货商/按库位四种模板，快速复用
- **盘点统计** (`StockCheck/statistics`)：月度趋势报表 + 差异原因分析
- 数据库新增 `stock_check`、`stock_check_detail`、`stock_check_template` 三张表

#### 商品管理模块
- **拼音码自动生成** (`PinyinService`)：输入商品名称自动生成拼音首字母编码，支持拼音搜索
- **商品扩展字段**：`pinyin_code`、`expiry_date`（到期日期）、`location`（库位）、`supplier_id`（供货商）
- **商品批量操作**：批量删除、批量上下架、批量修改分类

#### 出库管理模块
- **出库单** (`Outbound`)：支持销售出库和退货出库两种类型
- 出库单号格式：`CK + YYYYMMDD + 3位序列号`

#### 测试
- **StockCalculatorEdgeTest**：19 个测试（库存预警判断/估值计算/调整差异）
- **PinyinServiceTest**：7 个测试（中文/单字/ASCII/空白/边界）
- **OrderNoServiceEdgeTest**：新增 10 个测试（`generateOutboundNo` + `getNextSequenceForPrefix`）
- **OrderDisplayServiceTest**：新增 2 个边界测试（缺失字段/未知支付类型）
- 测试总数：103 → 全部通过，162 断言 0 失败

### 变更

- **StockCalculator**：新增 5 个方法
  - `isLowStock(int $stock, int $stockMin): bool` — 低库存判断
  - `isHighStock(int $stock, int $stockMax): bool` — 高库存判断
  - `isExpiring(int $expiryDate, int $daysThreshold): bool` — 临期判断
  - `calcStockValue(int $stock, float $purchasePrice): float` — 库存金额计算
  - `calcStockAdjustDiff(int $newStock, int $oldStock): int` — 调整差异计算
- **OrderNoService**：新增 `getNextSequenceForPrefix(?string $lastNo, string $prefix): int` — 带前缀的序列号解析
- **Stock::detail**：优先从 `stock_flow` 表查询流水，回退到联合查询
- **StockCheck::searchGoods**：空关键字时返回全部商品（修复 batchLoadAll 永远失败）
- **goods 表**：实际数据库补全 `pinyin_code`/`expiry_date`/`location`/`supplier_id` 列
- **索引补全**：`idx_goods_barcode`/`idx_goods_expiry`/`idx_goods_location`/`idx_goods_status`/`idx_purchase_no`/`idx_order_no`
- **菜单权限**：补全「盘点列表」「新建盘点」菜单，角色权限对齐

### 修复

#### Bug #1 — PinyinService UTF-8→GB2312 转换缺失
- **严重程度**：高
- **位置**：`app/service/PinyinService.php:getFirstLetter()`
- **问题**：直接取 UTF-8 多字节字符的原始字节值做 GB2312 区间比对，导致所有中文拼音码错误
  - 例如「水」返回 3 个字符而非 `s`
- **根因**：`ord($char[0])` 取的是 UTF-8 首字节，与 GB2312 编码不匹配
- **修复**：添加 `iconv('UTF-8', 'GB2312//IGNORE', $char)` 转换后再做区间匹配
- **发现**：TDD 周期 2，`PinyinServiceTest::testGeneratePinyinCodeForSingleChar`

#### Bug #2 — StockCheck::searchGoods 空关键字返回空
- **严重程度**：高
- **位置**：`app/controller/StockCheck.php:searchGoods()`
- **问题**：`keyword === ''` 时直接返回空数组，导致 `batchLoadAll()` 永远失败
- **修复**：空关键字时 `limit(500)` 返回全部商品

#### Bug #3 — schema.sql 含 DROP TABLE IF EXISTS goods_cate
- **严重程度**：高
- **位置**：`database/schema.sql`
- **问题**：DDL 中含 `DROP TABLE IF EXISTS goods_cate`，重复执行会破坏数据
- **修复**：改为 `CREATE TABLE IF NOT EXISTS`，移至正确的表编号位置

#### Bug #4 — 实际数据库与 schema 不一致
- **严重程度**：高
- **位置**：`database/shop.db`
- **问题**：
  - goods 表缺少 `pinyin_code`/`expiry_date`/`location`/`supplier_id` 列
  - 缺少 `stock_check`/`stock_check_detail`/`stock_check_template`/`stock_flow` 表
  - 缺少 6 个关键索引
  - 菜单权限缺少「盘点列表」「新建盘点」
- **修复**：
  - ALTER TABLE 补全 4 列
  - CREATE TABLE 补全 4 张表
  - CREATE INDEX 补全 6 个索引
  - INSERT INTO auth_rule 补全 2 个菜单
  - UPDATE role 更新角色权限

### 文档

- **TEST_REPORT.md**：完整测试报告（103 测试/162 断言/8 服务/4 TDD 周期）
- **schema.sql**：19 张表编号连续，33 个菜单项完整，3 个角色权限正确

---

## [2.0.0] — 2026-08-18

### 新增

- **出库管理**：出库单（销售出库/退货出库），出库单号 CK + 日期 + 序列号
- **库存预警**：低库存/高库存/临期商品三级预警，支持分类/供货商筛选
- **一键生成采购单**：从预警列表勾选商品，选择供货商，自动生成采购单
- **库存明细**：商品维度展示入库/出库/销售/盘点流水

### 变更

- **入库单**：进库单更名为入库单，收货人改为入库人
- **Purchase**：入库单号格式 JH + 日期 + 序列号
- **OrderNoService**：新增 `generateOutboundNo()` 方法

---

## [1.0.0] — 2026-08-18

### 新增

- 初始版本，基于 ThinkPHP 8.0 + SQLite 的便利店零售管理系统
- 权限管理（管理员/角色/规则）
- 商品管理（CRUD/分类/条码生成/导入导出）
- 入库管理（入库单/商品选择/箱规支持）
- 库存管理（库存总览/阈值设置）
- 订单管理（收银台/订单列表/订单导入）
- 会员管理（会员列表/分类/充值/充值记录）
- 供货商管理（CRUD/状态切换/导入导出）