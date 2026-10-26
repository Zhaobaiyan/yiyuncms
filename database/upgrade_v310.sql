-- ============================================================
-- 易云CMS v3.10 数据库升级脚本
-- 新功能：回收站 / 定时发布 / 评论 / 收藏 / 素材库 / 内容版本历史
--         / 搜索词日志 / 登录锁定+两步验证 / 会员登录 / 索引优化
-- 执行：mysql -uroot -p < upgrade_v310.sql
-- ============================================================

-- 1) 内容表：定时发布 + 软删除 + 索引
ALTER TABLE `yy_cn_content`
  ADD COLUMN `publish_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '定时发布时间(0=立即发布)' AFTER `reject_reason`,
  ADD COLUMN `delete_time`   int(10) unsigned NOT NULL DEFAULT 0 COMMENT '软删除时间(0=未删除)' AFTER `publish_time`,
  ADD KEY `idx_cat_status_del` (`catid`,`status`,`delete_time`),
  ADD KEY `idx_publish_time` (`publish_time`),
  ADD KEY `idx_title` (`title`),
  ADD KEY `idx_views` (`views`);

ALTER TABLE `yy_en_content`
  ADD COLUMN `publish_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '定时发布时间(0=立即发布)' AFTER `reject_reason`,
  ADD COLUMN `delete_time`   int(10) unsigned NOT NULL DEFAULT 0 COMMENT '软删除时间(0=未删除)' AFTER `publish_time`,
  ADD KEY `idx_cat_status_del` (`catid`,`status`,`delete_time`),
  ADD KEY `idx_publish_time` (`publish_time`),
  ADD KEY `idx_title` (`title`),
  ADD KEY `idx_views` (`views`);

-- 2) 栏目表：软删除
ALTER TABLE `yy_cn_category`
  ADD COLUMN `delete_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '软删除时间(0=未删除)' AFTER `update_time`,
  ADD KEY `idx_del_parent` (`delete_time`,`parent_id`);

ALTER TABLE `yy_en_category`
  ADD COLUMN `delete_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '软删除时间(0=未删除)' AFTER `update_time`,
  ADD KEY `idx_del_parent` (`delete_time`,`parent_id`);

-- 3) 管理员表：登录失败锁定 + 两步验证
ALTER TABLE `yy_admin`
  ADD COLUMN `login_fail` tinyint(4) NOT NULL DEFAULT 0 COMMENT '连续登录失败次数' AFTER `login_count`,
  ADD COLUMN `lock_time` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '锁定截止时间戳' AFTER `login_fail`,
  ADD COLUMN `twofa_secret` varchar(64) NOT NULL DEFAULT '' COMMENT '两步验证Secret' AFTER `lock_time`;

-- 4) 会员表：登录信息
ALTER TABLE `yy_member`
  ADD COLUMN `last_login_time` int(10) unsigned NOT NULL DEFAULT 0 AFTER `status`,
  ADD COLUMN `last_login_ip` varchar(50) NOT NULL DEFAULT '' AFTER `last_login_time`,
  ADD COLUMN `login_count` int(10) unsigned NOT NULL DEFAULT 0 AFTER `last_login_ip`;

-- 5) 新表：评论（每语言）
CREATE TABLE IF NOT EXISTS `yy_cn_comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '内容ID',
  `catid` int(10) unsigned NOT NULL DEFAULT 0,
  `member_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '会员ID(0=游客)',
  `username` varchar(80) NOT NULL DEFAULT '' COMMENT '昵称',
  `email` varchar(100) NOT NULL DEFAULT '',
  `content` text COMMENT '评论内容',
  `reply` text COMMENT '管理员回复',
  `reply_time` int(10) unsigned NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0待审 1通过 2驳回',
  `ip` varchar(50) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  `update_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`,`status`),
  KEY `idx_status` (`status`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论-cn';

CREATE TABLE IF NOT EXISTS `yy_en_comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT 0,
  `catid` int(10) unsigned NOT NULL DEFAULT 0,
  `member_id` int(10) unsigned NOT NULL DEFAULT 0,
  `username` varchar(80) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `content` text,
  `reply` text,
  `reply_time` int(10) unsigned NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `ip` varchar(50) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  `update_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`,`status`),
  KEY `idx_status` (`status`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论-en';

-- 6) 新表：收藏（每语言）
CREATE TABLE IF NOT EXISTS `yy_cn_favorite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT 0,
  `content_id` int(10) unsigned NOT NULL DEFAULT 0,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_content` (`member_id`,`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员收藏-cn';

CREATE TABLE IF NOT EXISTS `yy_en_favorite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL DEFAULT 0,
  `content_id` int(10) unsigned NOT NULL DEFAULT 0,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_content` (`member_id`,`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员收藏-en';

-- 7) 新表：素材库（每语言）
CREATE TABLE IF NOT EXISTS `yy_cn_asset` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '存储相对路径 /upload/asset/..',
  `type` varchar(10) NOT NULL DEFAULT 'image' COMMENT 'image/file',
  `ext` varchar(10) NOT NULL DEFAULT '',
  `size` int(10) unsigned NOT NULL DEFAULT 0,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_path` (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='素材库-cn';

CREATE TABLE IF NOT EXISTS `yy_en_asset` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `path` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(10) NOT NULL DEFAULT 'image',
  `ext` varchar(10) NOT NULL DEFAULT '',
  `size` int(10) unsigned NOT NULL DEFAULT 0,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_path` (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='素材库-en';

-- 8) 新表：内容版本历史（每语言）
CREATE TABLE IF NOT EXISTS `yy_cn_content_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT 0,
  `admin_id` int(10) unsigned NOT NULL DEFAULT 0,
  `action` varchar(20) NOT NULL DEFAULT 'edit' COMMENT 'save/rollback/audit/delete',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `content` mediumtext,
  `thumb` varchar(255) NOT NULL DEFAULT '',
  `log` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容版本历史-cn';

CREATE TABLE IF NOT EXISTS `yy_en_content_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT 0,
  `admin_id` int(10) unsigned NOT NULL DEFAULT 0,
  `action` varchar(20) NOT NULL DEFAULT 'edit',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `content` mediumtext,
  `thumb` varchar(255) NOT NULL DEFAULT '',
  `log` varchar(255) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容版本历史-en';

-- 9) 新表：搜索词日志（每语言）
CREATE TABLE IF NOT EXISTS `yy_cn_search_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(100) NOT NULL DEFAULT '',
  `result_count` int(10) unsigned NOT NULL DEFAULT 0,
  `ip` varchar(50) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_keyword` (`keyword`),
  KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='搜索词日志-cn';

CREATE TABLE IF NOT EXISTS `yy_en_search_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(100) NOT NULL DEFAULT '',
  `result_count` int(10) unsigned NOT NULL DEFAULT 0,
  `ip` varchar(50) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_keyword` (`keyword`),
  KEY `idx_create` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='搜索词日志-en';

-- 10) 大数据量索引（浏览日志/留言）
ALTER TABLE `yy_visit_log` ADD KEY `idx_date` (`create_time`) ;
ALTER TABLE `yy_cn_message` ADD KEY `idx_board_status` (`board_id`,`status`);
ALTER TABLE `yy_en_message` ADD KEY `idx_board_status` (`board_id`,`status`);
