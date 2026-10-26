-- =============================================================
-- 易云CMS v3.21 升级脚本：内容表增加「售卖商品」标记 is_shop
-- 幂等：可重复执行
-- 1) 遍历所有 yy_*_content 语言表，为每张表增加 is_shop 列
-- =============================================================
DROP PROCEDURE IF EXISTS `yy_upgrade_v321`;
DELIMITER $$
CREATE PROCEDURE `yy_upgrade_v321`()
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE tname VARCHAR(100);
  DECLARE cur CURSOR FOR
    SELECT table_name FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name LIKE 'yy\_%\_content';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  OPEN cur;
  loop_tbl: LOOP
    FETCH cur INTO tname;
    IF done = 1 THEN LEAVE loop_tbl; END IF;
    SET @sql = CONCAT('ALTER TABLE `', tname, '` ADD COLUMN `is_shop` tinyint(1) NOT NULL DEFAULT 0 AFTER `status`');
    SET @chk = CONCAT("SELECT COUNT(*) INTO @cnt FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='", tname, "' AND column_name='is_shop'");
    PREPARE s1 FROM @chk; EXECUTE s1; DEALLOCATE PREPARE s1;
    IF @cnt = 0 THEN
      PREPARE s2 FROM @sql; EXECUTE s2; DEALLOCATE PREPARE s2;
    END IF;
  END LOOP;
  CLOSE cur;
END$$
DELIMITER ;
CALL `yy_upgrade_v321`();
DROP PROCEDURE IF EXISTS `yy_upgrade_v321`;
