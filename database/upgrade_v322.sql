-- =============================================================
-- 易云CMS v3.22 升级脚本：后台菜单树表（拖动排序/改层级/行内改名）
-- 幂等：可重复执行
-- 1) 创建 yy_admin_menu 表（不存在时）
-- 2) 首次使用时由 Menu::ensureSeed() 自动从 AdminBase::defaultMenus() 导入种子
-- =============================================================
CREATE TABLE IF NOT EXISTS `yy_admin_menu` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '父级id，0=一级菜单组',
  `key` varchar(30) NOT NULL DEFAULT '' COMMENT '一级组内部标识（如 content），仅组行使用，改名不影响',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '显示名称',
  `icon` varchar(30) NOT NULL DEFAULT '' COMMENT '图标（仅组行使用）',
  `url` varchar(100) NOT NULL DEFAULT '' COMMENT '路由地址（子项必填；组可为空）',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '组内排序（数字越小越靠前）',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pid` (`pid`),
  KEY `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台菜单树（拖动排序/改层级/改名，v3.22）';
