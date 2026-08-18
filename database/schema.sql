-- 便利店零售管理系统 数据库建表脚本
-- SQLite3

-- 1. admin_user（管理员表）
CREATE TABLE IF NOT EXISTS admin_user (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(32) NOT NULL,
    role_id INTEGER DEFAULT 0,
    create_time INTEGER DEFAULT 0
);

-- 2. role（角色表）
CREATE TABLE IF NOT EXISTS role (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL,
    rules TEXT DEFAULT ''
);

-- 3. auth_rule（权限规则表）
CREATE TABLE IF NOT EXISTS auth_rule (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pid INTEGER DEFAULT 0,
    title VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT '',
    sort INTEGER DEFAULT 0
);

-- 4. supplier（供货商表）
CREATE TABLE IF NOT EXISTS supplier (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(50) DEFAULT '',
    phone VARCHAR(20) DEFAULT '',
    address VARCHAR(255) DEFAULT '',
    remark TEXT DEFAULT '',
    status TINYINT DEFAULT 1,
    create_time INTEGER DEFAULT 0
);

-- 5. goods（商品表）
CREATE TABLE IF NOT EXISTS goods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    barcode VARCHAR(50) NOT NULL,
    pinyin_code VARCHAR(100) DEFAULT '',
    unit VARCHAR(20) DEFAULT '',
    box_spec INTEGER DEFAULT 0,
    purchase_price DECIMAL(10,2) DEFAULT 0.00,
    retail_price DECIMAL(10,2) DEFAULT 0.00,
    stock INTEGER DEFAULT 0,
    stock_min INTEGER DEFAULT NULL,
    stock_max INTEGER DEFAULT NULL,
    expiry_date INTEGER DEFAULT NULL,
    location VARCHAR(50) DEFAULT '',
    cate VARCHAR(50) DEFAULT '',
    supplier_id INTEGER DEFAULT 0,
    status TINYINT DEFAULT 1,
    create_time INTEGER DEFAULT 0
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_goods_barcode ON goods(barcode);
CREATE INDEX IF NOT EXISTS idx_goods_expiry ON goods(expiry_date);
CREATE INDEX IF NOT EXISTS idx_goods_location ON goods(location);
CREATE INDEX IF NOT EXISTS idx_goods_status ON goods(status);

-- 6. purchase（进货主表）
CREATE TABLE IF NOT EXISTS purchase (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    purchase_no VARCHAR(30) NOT NULL,
    supplier_id INTEGER DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    total_goods_num INTEGER DEFAULT 0,
    operator_id INTEGER DEFAULT 0,
    remark TEXT DEFAULT '',
    create_time INTEGER DEFAULT 0
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_purchase_no ON purchase(purchase_no);

-- 7. purchase_detail（进货明细表）
CREATE TABLE IF NOT EXISTS purchase_detail (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    purchase_id INTEGER DEFAULT 0,
    barcode VARCHAR(50) NOT NULL,
    goods_name VARCHAR(100) NOT NULL,
    unit VARCHAR(20) DEFAULT '',
    purchase_price DECIMAL(10,2) DEFAULT 0.00,
    retail_price DECIMAL(10,2) DEFAULT 0.00,
    box_spec INTEGER DEFAULT 0,
    box_count INTEGER DEFAULT 0,
    piece_count INTEGER DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    create_time INTEGER DEFAULT 0
);

-- 8. order（订单主表，避免关键字冲突用方括号）
CREATE TABLE IF NOT EXISTS [order] (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_no VARCHAR(30) NOT NULL,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    pay_amount DECIMAL(10,2) DEFAULT 0.00,
    pay_type TINYINT DEFAULT 1,
    member_id INTEGER DEFAULT 0,
    operator_id INTEGER DEFAULT 0,
    remark VARCHAR(255) DEFAULT '',
    create_time INTEGER DEFAULT 0
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_order_no ON [order](order_no);

-- 9. order_detail（订单明细表）
CREATE TABLE IF NOT EXISTS order_detail (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER DEFAULT 0,
    barcode VARCHAR(50) NOT NULL,
    goods_name VARCHAR(100) NOT NULL,
    retail_price DECIMAL(10,2) DEFAULT 0.00,
    purchase_price DECIMAL(10,2) DEFAULT 0.00,
    quantity INTEGER DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    pay_amount DECIMAL(10,2) DEFAULT 0.00,
    create_time INTEGER DEFAULT 0
);

-- 10. member_cate（会员分类表）
CREATE TABLE IF NOT EXISTS member_cate (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 1.00,
    create_time INTEGER DEFAULT 0
);

-- 11. member（会员表）
CREATE TABLE IF NOT EXISTS member (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    cate_id INTEGER DEFAULT 0,
    balance DECIMAL(10,2) DEFAULT 0.00,
    remark VARCHAR(255) DEFAULT '',
    create_time INTEGER DEFAULT 0
);

-- 13. outbound（出库主表）
CREATE TABLE IF NOT EXISTS outbound (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    outbound_no VARCHAR(30) NOT NULL,
    type TINYINT DEFAULT 1,
    supplier_id INTEGER DEFAULT 0,
    order_id INTEGER DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    total_goods_num INTEGER DEFAULT 0,
    operator_id INTEGER DEFAULT 0,
    remark TEXT DEFAULT '',
    create_time INTEGER DEFAULT 0
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_outbound_no ON outbound(outbound_no);

-- 14. outbound_detail（出库明细表）
CREATE TABLE IF NOT EXISTS outbound_detail (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    outbound_id INTEGER DEFAULT 0,
    barcode VARCHAR(50) NOT NULL,
    goods_name VARCHAR(100) NOT NULL,
    unit VARCHAR(20) DEFAULT '',
    quantity INTEGER DEFAULT 0,
    purchase_price DECIMAL(10,2) DEFAULT 0.00,
    retail_price DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    create_time INTEGER DEFAULT 0
);
CREATE TABLE IF NOT EXISTS member_recharge (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    member_id INTEGER DEFAULT 0,
    amount DECIMAL(10,2) DEFAULT 0.00,
    before_balance DECIMAL(10,2) DEFAULT 0.00,
    after_balance DECIMAL(10,2) DEFAULT 0.00,
    operator_id INTEGER DEFAULT 0,
    create_time INTEGER DEFAULT 0
);

-- 商品分类表
DROP TABLE IF EXISTS goods_cate;
CREATE TABLE goods_cate (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL DEFAULT '',
    status TINYINT DEFAULT 1,
    create_time INTEGER DEFAULT 0
);

-- 15. stock_check（盘点主表）
CREATE TABLE IF NOT EXISTS stock_check (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    check_no VARCHAR(30) NOT NULL,
    status TINYINT DEFAULT 0,
    total_goods_num INTEGER DEFAULT 0,
    profit_num INTEGER DEFAULT 0,
    loss_num INTEGER DEFAULT 0,
    profit_amount DECIMAL(10,2) DEFAULT 0.00,
    loss_amount DECIMAL(10,2) DEFAULT 0.00,
    operator_id INTEGER DEFAULT 0,
    auditor_id INTEGER DEFAULT 0,
    audit_time INTEGER DEFAULT NULL,
    remark TEXT DEFAULT '',
    create_time INTEGER DEFAULT 0
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_check_no ON stock_check(check_no);

-- 16. stock_check_detail（盘点明细表）
CREATE TABLE IF NOT EXISTS stock_check_detail (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    check_id INTEGER DEFAULT 0,
    barcode VARCHAR(50) NOT NULL,
    goods_name VARCHAR(100) NOT NULL,
    unit VARCHAR(20) DEFAULT '',
    book_stock INTEGER DEFAULT 0,
    actual_stock INTEGER DEFAULT 0,
    diff INTEGER DEFAULT 0,
    purchase_price DECIMAL(10,2) DEFAULT 0.00,
    diff_amount DECIMAL(10,2) DEFAULT 0.00,
    reason VARCHAR(30) DEFAULT '',
    remark VARCHAR(255) DEFAULT '',
    create_time INTEGER DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_check_detail_id ON stock_check_detail(check_id);

-- 17. stock_check_template（盘点模板表）
CREATE TABLE IF NOT EXISTS stock_check_template (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL,
    type VARCHAR(20) DEFAULT 'full',
    cate VARCHAR(50) DEFAULT '',
    supplier_id INTEGER DEFAULT 0,
    location VARCHAR(50) DEFAULT '',
    remark TEXT DEFAULT '',
    create_time INTEGER DEFAULT 0
);

-- ========== 种子数据 ==========

-- 角色数据
INSERT INTO role (name, rules) VALUES ('超级管理员', '');
INSERT INTO role (name, rules) VALUES ('店长', '');
INSERT INTO role (name, rules) VALUES ('收银员', '');

-- 默认管理员 admin/admin123
INSERT INTO admin_user (username, password, role_id, create_time) VALUES ('admin', '0192023a7bbd73250516f069df18b500', 1, 0);

-- 权限规则（菜单树）
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (0, '首页', 'Index/index', 'layui-icon-home', 1);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (0, '权限管理', '', 'layui-icon-vercode', 2);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (2, '权限规则', 'Auth/ruleList', '', 1);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (2, '角色管理', 'Auth/roleList', '', 2);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (2, '管理员管理', 'Auth/adminList', '', 3);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (0, '供货商管理', 'Supplier/index', 'layui-icon-user', 3);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (0, '商品管理', '', 'layui-icon-component', 4);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (7, '商品列表', 'Goods/index', '', 1);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (7, '商品分类', 'Goods/cateList', '', 2);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (0, '入库管理', '', 'layui-icon-template-1', 5);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (10, '入库列表', 'Purchase/index', '', 1);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (10, '新建入库', 'Purchase/add', '', 2);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (0, '出库管理', '', 'layui-icon-logout', 10);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (23, '出库列表', 'Outbound/index', '', 1);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (23, '新建出库', 'Outbound/add', '', 2);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (0, '库存管理', '', 'layui-icon-chart-screen', 6);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (13, '库存总览', 'Stock/index', '', 1);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (13, '库存预警', 'Stock/warning', '', 2);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (0, '盘点管理', '', 'layui-icon-survey', 11);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (25, '盘点列表', 'StockCheck/index', '', 1);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (25, '新建盘点', 'StockCheck/add', '', 2);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (25, '盘点模板', 'StockCheck/template', '', 3);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (25, '盘点统计', 'StockCheck/statistics', '', 4);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (0, '收银台', 'Cashier/index', 'layui-icon-rmb', 7);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (0, '订单管理', 'Order/index', 'layui-icon-list', 8);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (0, '会员管理', '', 'layui-icon-username', 9);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (18, '会员分类', 'Member/cateList', '', 1);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (18, '会员列表', 'Member/index', '', 2);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (18, '会员充值', 'Member/recharge', '', 3);
INSERT INTO auth_rule (pid, title, name, icon, sort) VALUES (18, '充值记录', 'Member/rechargeLog', '', 4);

-- 更新超级管理员角色的权限规则（所有权限ID）
UPDATE role SET rules = '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29' WHERE id = 1;
-- 店长：所有业务模块（不含权限管理 2-5）
UPDATE role SET rules = '1,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29' WHERE id = 2;
-- 收银员：仅首页、收银台、订单管理
UPDATE role SET rules = '1,16,17' WHERE id = 3;

-- 会员分类种子数据
INSERT INTO member_cate (name, discount, create_time) VALUES ('普通会员', 0.98, 0);
INSERT INTO member_cate (name, discount, create_time) VALUES ('金牌会员', 0.95, 0);
INSERT INTO member_cate (name, discount, create_time) VALUES ('钻石会员', 0.90, 0);

-- 商品分类种子数据
INSERT INTO goods_cate (name, create_time) VALUES ('饮料', 0);
INSERT INTO goods_cate (name, create_time) VALUES ('零食', 0);
INSERT INTO goods_cate (name, create_time) VALUES ('日用品', 0);
INSERT INTO goods_cate (name, create_time) VALUES ('烟酒', 0);
INSERT INTO goods_cate (name, create_time) VALUES ('调味品', 0);
