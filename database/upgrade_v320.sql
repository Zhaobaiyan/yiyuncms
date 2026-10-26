-- ============================================================
-- 易云CMS v3.20 站群推送与分站管理员升级脚本（幂等，可重复执行）
-- 1. yy_push 跨站/跨语言内容推送表（全局共享表）
-- 2. yy_admin.site_scope 管理员可管理站点/语言范围（JSON，空=全部）
-- 执行：mysql -uroot 库名 < database/upgrade_v320.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `yy_push` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `from_site` varchar(50) NOT NULL DEFAULT 'main' COMMENT '来源站点（main=主站，否则=站点别名）',
  `to_site` varchar(50) NOT NULL DEFAULT 'main' COMMENT '目标站点（main=主站，否则=站点别名）',
  `from_lang` varchar(20) NOT NULL DEFAULT '' COMMENT '来源语言代码',
  `from_catid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '来源栏目ID',
  `from_content_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '来源内容ID',
  `to_lang` varchar(20) NOT NULL DEFAULT '' COMMENT '目标语言代码',
  `to_catid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '目标栏目ID（处理时确定）',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '推送标题快照',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0待处理 1已采纳发布 2已拒绝 3已撤回',
  `target_content_id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '采纳后在目标语言生成的内容ID',
  `creator_uid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '推送发起管理员ID',
  `creator_name` varchar(50) NOT NULL DEFAULT '' COMMENT '推送发起管理员昵称',
  `audit_uid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '处理管理员ID',
  `audit_name` varchar(50) NOT NULL DEFAULT '' COMMENT '处理管理员昵称',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '拒绝理由/处理备注',
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  `audit_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_to` (`to_site`,`status`),
  KEY `idx_from` (`from_site`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='跨站/跨语言内容推送记录（v3.20）';

-- 管理员可管理站点/语言范围（JSON 数组，如 ["cn","shenyang"]；空=全部）
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yy_admin' AND COLUMN_NAME='site_scope');
SET @sql := IF(@col=0, 'ALTER TABLE `yy_admin` ADD COLUMN `site_scope` varchar(500) NOT NULL DEFAULT \'\' COMMENT \'可管理站点/语言代码JSON，空=全部\' AFTER `role`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
