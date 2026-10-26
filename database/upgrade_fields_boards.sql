-- 模型扩展字段表 + 留言板表（cn/en），供新语言经 LIKE 复制
USE newcms;

CREATE TABLE IF NOT EXISTS `yy_cn_model_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `model_id` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(50) NOT NULL DEFAULT '',
  `label` varchar(80) NOT NULL DEFAULT '',
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `options` text,
  `required` tinyint(4) NOT NULL DEFAULT 0,
  `sort` int(11) NOT NULL DEFAULT 100,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  `update_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_model` (`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模型扩展字段-cn';

CREATE TABLE IF NOT EXISTS `yy_en_model_field` LIKE `yy_cn_model_field`;
ALTER TABLE `yy_en_model_field` COMMENT='模型扩展字段-en';

CREATE TABLE IF NOT EXISTS `yy_cn_message_board` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL DEFAULT '',
  `title` varchar(80) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `fields` text,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `sort` int(11) NOT NULL DEFAULT 100,
  `create_time` int(10) unsigned NOT NULL DEFAULT 0,
  `update_time` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='留言板-cn';

CREATE TABLE IF NOT EXISTS `yy_en_message_board` LIKE `yy_cn_message_board`;
ALTER TABLE `yy_en_message_board` COMMENT='留言板-en';

-- 留言表加留言板归属与扩展字段
ALTER TABLE `yy_cn_message` ADD COLUMN `board_id` int(10) unsigned NOT NULL DEFAULT 0 AFTER `id`;
ALTER TABLE `yy_cn_message` ADD COLUMN `extend` text;
ALTER TABLE `yy_en_message` ADD COLUMN `board_id` int(10) unsigned NOT NULL DEFAULT 0 AFTER `id`;
ALTER TABLE `yy_en_message` ADD COLUMN `extend` text;

-- 默认留言板（id=1）
INSERT INTO `yy_cn_message_board` (`id`,`name`,`title`,`description`,`fields`,`status`,`sort`,`create_time`,`update_time`) VALUES
(1,'default','在线留言','欢迎留言，我们会尽快与您联系',
 '[{"name":"name","label":"称呼","type":"text","required":1,"sort":1},{"name":"phone","label":"联系电话","type":"text","required":0,"sort":2},{"name":"email","label":"电子邮箱","type":"text","required":0,"sort":3},{"name":"title","label":"留言主题","type":"text","required":0,"sort":4},{"name":"content","label":"留言内容","type":"textarea","required":1,"sort":5}]',
 1,100,UNIX_TIMESTAMP(),UNIX_TIMESTAMP());

INSERT INTO `yy_en_message_board` (`id`,`name`,`title`,`description`,`fields`,`status`,`sort`,`create_time`,`update_time`) VALUES
(1,'default','Online Message','Leave a message, we will contact you soon',
 '[{"name":"name","label":"Name","type":"text","required":1,"sort":1},{"name":"phone","label":"Phone","type":"text","required":0,"sort":2},{"name":"email","label":"Email","type":"text","required":0,"sort":3},{"name":"title","label":"Subject","type":"text","required":0,"sort":4},{"name":"content","label":"Content","type":"textarea","required":1,"sort":5}]',
 1,100,UNIX_TIMESTAMP(),UNIX_TIMESTAMP());

SELECT 'model_field' t, COUNT(*) c FROM yy_cn_model_field UNION ALL
SELECT 'message_board', COUNT(*) FROM yy_cn_message_board;
