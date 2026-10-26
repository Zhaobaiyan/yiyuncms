-- 升级脚本：管理员角色表 + 内置角色
CREATE TABLE IF NOT EXISTS `yy_admin_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '角色标识(唯一)',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '角色名称',
  `rules` text COMMENT '允许控制器白名单JSON(* 或 ["content",...])',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `is_system` tinyint(1) NOT NULL DEFAULT 0 COMMENT '内置角色不可删除',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int(11) NOT NULL DEFAULT 0,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  `update_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台管理员角色';

INSERT IGNORE INTO `yy_admin_role` (`code`,`name`,`rules`,`remark`,`is_system`,`status`,`sort`,`create_time`,`update_time`) VALUES
('super','超级管理员','*','拥有全部权限',1,1,0,0,0),
('admin','普通管理员','[]','默认后台账号，可配置权限',1,1,1,0,0),
('tougao','投稿员','["content","upload","ueditor"]','仅可发布/管理内容',0,1,2,0,0),
('message','留言查看','["message","board"]','仅可查看留言',0,1,3,0,0),
('seo','SEO优化','["link","setting"]','可管理友情链接等SEO相关',0,1,4,0,0);
