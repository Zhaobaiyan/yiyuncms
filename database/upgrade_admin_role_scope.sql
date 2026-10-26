-- 升级脚本：管理员角色 数据范围（栏目范围 / 留言板范围）
-- 字段格式均为 JSON，按语言分组：{"cn":[1,2],"en":[3,4]}；某语言缺失或空数组 = 该语言下不限制（全部）
ALTER TABLE `yy_admin_role`
  ADD COLUMN `cat_ids` TEXT NULL COMMENT '可管理栏目ID(JSON按语言分组: {"cn":[1,2],"en":[]})' AFTER `rules`,
  ADD COLUMN `board_ids` TEXT NULL COMMENT '可查看留言板ID(JSON按语言分组)' AFTER `cat_ids`;
