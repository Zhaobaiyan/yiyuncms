-- 易云CMS v3.10 增量：收藏表支持匿名收藏（member_id 改为 varchar 存匿名唯一标识）
ALTER TABLE `yy_cn_favorite` MODIFY `member_id` varchar(64) NOT NULL DEFAULT '' COMMENT '会员ID(数字)或匿名收藏标识', DROP INDEX `uk_member_content`, ADD KEY `idx_member_content` (`member_id`,`content_id`);
ALTER TABLE `yy_en_favorite` MODIFY `member_id` varchar(64) NOT NULL DEFAULT '' COMMENT '会员ID(数字)或匿名收藏标识', DROP INDEX `uk_member_content`, ADD KEY `idx_member_content` (`member_id`,`content_id`);
