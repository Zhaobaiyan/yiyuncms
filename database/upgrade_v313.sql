-- ============================================================
-- 易云CMS v3.13 站群系统升级脚本（幂等）
-- 新增 yy_site 站点表 + 站群开关配置
-- ============================================================

CREATE TABLE IF NOT EXISTS `yy_site` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '站点名称',
  `domain` varchar(200) NOT NULL DEFAULT '' COMMENT '域名匹配（支持 * 通配，如 *.example.com 或 newcms.com:8080）',
  `default_lang` varchar(20) NOT NULL DEFAULT 'cn' COMMENT '站点默认语言',
  `langs` text NULL COMMENT '站点启用语言 JSON 数组（空=全部已启用语言）',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1开启 0关闭',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='站群站点';

-- 站群开关（0=关 单站点模式，1=开 站群模式）
INSERT INTO `yy_config` (`name`,`value`) VALUES ('site.multi','0')
ON DUPLICATE KEY UPDATE `value` = `value`;
