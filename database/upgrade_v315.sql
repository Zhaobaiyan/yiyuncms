-- ============================================================
-- 易云CMS v3.15：线上支付(模拟微信/支付宝) + 商品规格SKU + 订单状态机 + 售后退款 + 钱包提现
-- 幂等：字段已存在则跳过；新表不存在则创建
-- ============================================================

-- 1. yy_shop 商品规格模板（JSON：{"颜色":["红","蓝"],"尺寸":["X","XL","XXL"]}）
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='newcms' AND TABLE_NAME='yy_shop' AND COLUMN_NAME='specs');
SET @sql := IF(@col=0, 'ALTER TABLE yy_shop ADD COLUMN `specs` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT ''规格模板JSON'' AFTER `status`', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- 2. yy_cart 已选规格（如 "颜色:红,尺寸:XL"）
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='newcms' AND TABLE_NAME='yy_cart' AND COLUMN_NAME='spec');
SET @sql := IF(@col=0, 'ALTER TABLE yy_cart ADD COLUMN `spec` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '''' COMMENT ''已选规格'' AFTER `num`', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- 3. yy_order 快递与售后状态
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='newcms' AND TABLE_NAME='yy_order' AND COLUMN_NAME='express_company');
SET @sql := IF(@col=0, 'ALTER TABLE yy_order ADD COLUMN `express_company` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '''' COMMENT ''快递公司'' AFTER `pay_time`', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='newcms' AND TABLE_NAME='yy_order' AND COLUMN_NAME='express_no');
SET @sql := IF(@col=0, 'ALTER TABLE yy_order ADD COLUMN `express_no` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '''' COMMENT ''快递单号'' AFTER `express_company`', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='newcms' AND TABLE_NAME='yy_order' AND COLUMN_NAME='ship_time');
SET @sql := IF(@col=0, 'ALTER TABLE yy_order ADD COLUMN `ship_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT ''发货时间'' AFTER `express_no`', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='newcms' AND TABLE_NAME='yy_order' AND COLUMN_NAME='confirm_time');
SET @sql := IF(@col=0, 'ALTER TABLE yy_order ADD COLUMN `confirm_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT ''确认收货时间'' AFTER `ship_time`', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='newcms' AND TABLE_NAME='yy_order' AND COLUMN_NAME='refund_status');
SET @sql := IF(@col=0, 'ALTER TABLE yy_order ADD COLUMN `refund_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''0无售后 1申请中 2已同意 3已退款 4已拒绝'' AFTER `confirm_time`', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- 4. yy_order_product 规格快照
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='newcms' AND TABLE_NAME='yy_order_product' AND COLUMN_NAME='spec');
SET @sql := IF(@col=0, 'ALTER TABLE yy_order_product ADD COLUMN `spec` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '''' COMMENT ''规格快照'' AFTER `num`', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- 5. 售后申请表 yy_refund
CREATE TABLE IF NOT EXISTS `yy_refund` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'refund_only' COMMENT 'refund_only仅退款 return_goods退货退款',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '申请金额',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '退款原因',
  `desc` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '问题描述',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0申请中 1已同意 2已拒绝 3已退款',
  `admin_reply` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '客服回复',
  `handle_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='售后/退款申请';

-- 6. 会员钱包 yy_wallet
CREATE TABLE IF NOT EXISTS `yy_wallet` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '可用余额',
  `frozen` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '提现冻结中',
  `total_income` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '累计收入(退款/充值)',
  `total_withdraw` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '累计提现',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员钱包';

-- 7. 钱包流水 yy_wallet_log
CREATE TABLE IF NOT EXISTS `yy_wallet_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'refund退款 withdraw提现 adjust调整 income收入',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变动金额(正加负减)',
  `balance_after` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变动后余额',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='钱包流水';

-- 8. 提现申请 yy_withdraw
CREATE TABLE IF NOT EXISTS `yy_withdraw` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '提现金额',
  `channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'wechat' COMMENT 'wechat微信 alipay支付宝',
  `account` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '收款账号',
  `real_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '收款人姓名',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0申请中 1已打款 2已拒绝',
  `admin_note` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '后台备注',
  `handle_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提现申请';

-- 9. 支付配置（模拟微信/支付宝，默认开启；真实渠道需商户号，本地用模拟模式）
INSERT INTO yy_config (name,value,create_time,update_time) VALUES
 ('shop.pay_wechat','1',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
 ('shop.pay_alipay','1',UNIX_TIMESTAMP(),UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE value=VALUES(value), update_time=UNIX_TIMESTAMP();
