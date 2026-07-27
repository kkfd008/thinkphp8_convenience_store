# Checklist

## 第一阶段：项目初始化与基础设施
- [x] ThinkPHP 8 项目正常创建，入口可访问
- [x] `.env` 配置 SQLite/MySQL 双数据库驱动，`DB_DRIVER` 切换正常
- [x] `database/shop.db` 生成成功，13 张表均已创建
- [x] 默认管理员账号 admin/admin123 可登录
- [x] Layui 2.9.10 静态资源可正常加载
- [x] ECharts 静态资源可正常加载
- [x] `view/layout.html` 公共布局渲染正常（左侧菜单区 + 面包屑 + 内容区）

## 第二阶段：权限认证模块
- [x] 登录页 `view/login/index.html` 样式正确，居中显示
- [x] 正确账号密码可登录，错误密码提示错误
- [x] 退出登录后 Session 清除，跳转登录页
- [x] 未登录直接访问后台 URL 自动跳转登录页
- [x] 无权限访问返回 403
- [x] 登录表单 `autocomplete="username"` / `autocomplete="current-password"`

## 第三阶段：权限配置管理
- [x] 权限规则树形表格展示正确（`view/auth/rule_list.html`），新增/编辑子权限功能正常
- [x] 删除权限时提示确认
- [x] 角色列表展示正确（`view/auth/role_list.html`），编辑角色时权限树勾选/回显正常
- [x] 管理员列表展示正确（`view/auth/admin_list.html`），新增/编辑管理员功能正常
- [x] 共 22 条权限规则，三级角色权限隔离正确

## 第四阶段：供货商管理
- [x] 供货商列表支持搜索（名称/电话）、状态筛选
- [x] 新增/编辑供货商功能正常（弹窗表单，含电话 type="tel"）
- [x] 已关联进货单的供货商删除被拒绝
- [x] 禁用供货商后在进货新建页不再可选
- [x] Excel 导入供货商功能正常
- [x] Excel 导出供货商功能正常
- [x] 导入模板下载功能正常

## 第五阶段：商品管理
- [x] 商品列表支持名称/条码/分类搜索，支持 checkbox 多选
- [x] 新增商品随机生成条码功能正常（13位数字唯一，genBarcode 接口）
- [x] 条码唯一性校验正常（checkBarcode 接口）
- [x] 已关联进货/订单的商品条码不可修改（readonly）
- [x] 已关联进货/订单的商品不可删除
- [x] 分类管理增删改功能正常（`view/goods/cate_list.html`），含显示/隐藏状态切换
- [x] 扫码查询：搜索框输入条码回车可命中
- [x] Excel 导入：模板下载、上传解析、校验去重、导入结果展示均正常
- [x] 批量修改分类（batchCate）功能正常

## 第六阶段：进货管理
- [x] 新建进货单：供货商选择（lay-search 下拉）、商品搜索添加、箱规/箱数/散件填写、金额自动计算均正常
- [x] 进货单号生成规则正确（JH+Ymd+三位流水号）
- [x] 保存后 stock 正确累加（stock += box_spec × box_count + piece_count），goods.box_spec 同步更新
- [x] 进货单不可删除
- [x] 进货历史列表搜索/筛选/分页正常（按单号/供货商/时间范围）
- [x] 进货明细查看正常（`view/purchase/detail.html`，含箱规/箱数/散件计算）
- [x] 进货 Excel 单行导入：模板下载、校验跳过无效行、有效数据自动入库均正常
- [x] 多 Sheet 复杂格式导入（importSheets）：自动解析表头/日期、按 Sheet 分单、自动创建商品、结果展示弹窗均正常
- [x] 进货数据导出功能正常

## 第七阶段：库存管理与预警
- [x] 库存总览：低于 stock_min 标红（CSS class `row-stock-low`）、高于 stock_max 标黄（CSS class `row-stock-high`）
- [x] 库存总览：内联编辑预警阈值正常（Layui table edit，updateThreshold 接口）
- [x] 预警页按预警类型筛选正常（全部/低于最小/高于最大，URL query type 参数）
- [x] 预警页"编辑阈值"弹窗修改正常
- [x] 库存流水明细查询（stock/detail）：按 barcode 追溯所有进货流入和销售流出记录，弹窗展示正常
- [x] 库存数据导出功能正常
- [x] 预警数据导出功能正常

## 第八阶段：收银台
- [x] 扫码/搜索加购功能正常（searchGoods 接口）
- [x] 购物车数量 +/-、删除操作正常（不超过库存校验）
- [x] 会员搜索选择后折扣自动计算（searchMember 接口，关联 member_cate.discount）
- [x] 现金支付结算成功，库存扣减正确
- [x] 会员余额支付：余额足够时扣减 balance 正确
- [x] 会员余额支付：余额不足时拒绝结算
- [x] 库存不足时拒绝结算
- [x] 订单号生成格式正确（DD+Ymd+三位流水号）
- [x] 搜索结果项使用 `<button type="button">` 元素

## 第九阶段：订单管理
- [x] 订单列表按时间/单号筛选正常
- [x] 订单明细查看正常（含进货价用于毛利分析）
- [x] 收银员仅看到本人订单（role_id=3 时过滤 operator_id）
- [x] 订单数据导出功能正常

## 第十阶段：会员管理
- [x] 会员分类增删改查正常，折扣率设置生效
- [x] 会员档案增删改查正常，手机号唯一校验生效
- [x] 有交易记录会员不可删除
- [x] 有关联会员的分类不可删除
- [x] 会员充值余额正确累加，充值记录前后余额准确（member_recharge 表）
- [x] 充值记录列表筛选查询正常（按会员/时间范围）
- [x] 会员数据导出功能正常
- [x] 会员充值页搜索结果项使用 `<button>` 元素，输入框 `autocomplete="off"`

## 第十一阶段：数据统计
- [x] 首页数字卡片展示今日/累计营收、订单数、基础数据（商品/会员/供货商数）
- [x] ECharts 近15日销售趋势图正常渲染（折线图，smooth: true）
- [x] ECharts 近15日进货趋势图正常渲染（双折线叠加）
- [x] ECharts 商品销量 TOP10 横向柱状图正常渲染（inverse: true）
- [x] 窗口 resize 时图表自适应

## 第十二阶段：Excel 导入导出
- [x] 商品导出 XLSX 格式正确，时间转为日期、金额保留两位小数
- [x] 供货商导出、进货单导出、订单导出、会员导出、库存导出、预警导出均正常
- [x] 商品导入模板下载正确
- [x] 供货商导入模板下载正确
- [x] 进货单导入模板下载正确

## 第十三阶段：Web Interface Guidelines 合规
- [x] 所有 Layui table 工具栏 `<a lay-event>` 已替换为 `<button lay-event>`（16 个文件）
- [x] 搜索结果列表项使用 `<button type="button">`（收银台、进货新建、会员充值）
- [x] 登录按钮添加 loading 状态（"登录中…"）
- [x] `layout.html` 添加 `:focus-visible` 全局焦点样式（outline: 2px solid #009688）
- [x] 输入框 `:focus` 添加 box-shadow 增强
- [x] `<html>` 设置 `touch-action: manipulation` 和 `-webkit-tap-highlight-color: transparent`
- [x] 手机号输入框 `type="text"` → `type="tel"`（会员编辑、供货商编辑）
- [x] 所有 placeholder 以 `…` 结尾（约为 20 处）
- [x] 搜索框添加隐藏 `<label>` 供屏幕阅读器识别
- [x] 按钮添加 `aria-label`（商品管理页）
- [x] 库存预警行内联样式替换为 CSS class（`row-stock-low`、`row-stock-high`、`row-warning`）
- [x] 无 `<div onclick>` 反模式残留
- [x] 无 `...`（三个点）残留，全部替换为 `…`（省略号）

## 第十四阶段：联调测试与收尾
- [x] admin 登录全流程测试通过（所有模块可正常操作）
- [x] 店长角色权限验证通过（无权限配置入口，业务功能完整）
- [x] 收银员角色权限验证通过（仅收银台+本人订单可见）
- [x] PRD.md 文档已创建，基于实际代码实现整理
- [x] README.md 部署说明清晰可用