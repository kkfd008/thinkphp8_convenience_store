-- 增量迁移：为 order_detail 表添加 discount_amount 和 pay_amount 列
-- 执行方式：sqlite3 database/shop.db < database/migrate_add_order_detail_columns.sql

ALTER TABLE order_detail ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE order_detail ADD COLUMN pay_amount DECIMAL(10,2) DEFAULT 0.00;