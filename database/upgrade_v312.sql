-- ============================================================
-- 易云CMS v3.12：会员审核 + 商城 + 客服 数据表
-- 数据库：newcms（前缀 yy_）  utf8mb4 / InnoDB
-- ============================================================

-- 1. 会员表增加审核字段
ALTER TABLE yy_member ADD COLUMN audit_remark varchar(255) NOT NULL DEFAULT '' COMMENT '审核备注（拒绝原因等）' AFTER status;
ALTER TABLE yy_member ADD COLUMN reg_ip varchar(50) NOT NULL DEFAULT '' COMMENT '注册IP' AFTER audit_remark;

-- 2. 商城商品表（lang 区分语言内容）
CREATE TABLE IF NOT EXISTS `yy_shop` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lang` varchar(10) NOT NULL DEFAULT 'cn' COMMENT '语言',
  `conid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID（对应 yy_{lang}_content.id）',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '售价',
  `original_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原价',
  `stock` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '库存',
  `sales` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '销量',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1上架 0下架',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lang_conid` (`lang`,`conid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商城商品';

-- 3. 购物车表
CREATE TABLE IF NOT EXISTS `yy_cart` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `lang` varchar(10) NOT NULL DEFAULT 'cn' COMMENT '语言',
  `conid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `num` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '数量',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='购物车';

-- 4. 订单表
CREATE TABLE IF NOT EXISTS `yy_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号',
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `lang` varchar(10) NOT NULL DEFAULT 'cn' COMMENT '语言',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '总额',
  `pay_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '应付',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待支付 1已支付 2已发货 3已完成 4已取消',
  `pay_type` varchar(20) NOT NULL DEFAULT '' COMMENT '支付方式 test/offline',
  `pay_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付时间',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '收货人',
  `mobile` varchar(30) NOT NULL DEFAULT '' COMMENT '联系电话',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '收货地址',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单';

-- 5. 订单商品表
CREATE TABLE IF NOT EXISTS `yy_order_product` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `conid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名快照',
  `pic` varchar(255) NOT NULL DEFAULT '' COMMENT '图片快照',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单价快照',
  `num` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '数量',
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单商品';

-- 6. 客服会话表
CREATE TABLE IF NOT EXISTS `yy_service_session` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '接待管理员ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待接入 1进行中 2已关闭',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `last_msg_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后消息时间',
  PRIMARY KEY (`id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客服会话';

-- 7. 客服消息表
CREATE TABLE IF NOT EXISTS `yy_service_msg` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '会话ID',
  `from_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1会员 2客服',
  `from_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发送人ID',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '消息内容',
  `is_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已读（0未读1已读）',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客服消息';

-- 8. 全局配置键
INSERT INTO yy_config (`name`,`value`) VALUES
('member.reg_open','1'),
('member.reg_captcha','1'),
('member.audit','0'),
('shop.pay_test','1'),
('shop.pay_offline','1'),
('service.open','1')
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);

-- 校验
SHOW TABLES LIKE 'yy_shop';
SHOW TABLES LIKE 'yy_cart';
SHOW TABLES LIKE 'yy_order';
SHOW TABLES LIKE 'yy_order_product';
SHOW TABLES LIKE 'yy_service_%';
SELECT name,value FROM yy_config WHERE name IN ('member.reg_open','member.reg_captcha','member.audit','shop.pay_test','shop.pay_offline','service.open');
