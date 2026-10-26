-- ============================================================
-- 易云CMS v3.18 升级脚本：站群路径模式 + 多语言模板复制
-- 运行方式：mysql -uroot -p newcms < upgrade_v318.sql
-- 幂等：可重复执行
-- ============================================================

-- 1) 站群站点表增加：路径别名 alias / 站点模式 mode
--    mode=domain（按域名命中，原行为）| mode=path（本域名+路径别名，如 newcms.com/shenyang/）
SET @has_alias := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='yy_site' AND column_name='alias');
SET @sql_alias := IF(@has_alias=0, 'ALTER TABLE yy_site ADD COLUMN alias varchar(50) NOT NULL DEFAULT '''' COMMENT ''路径别名(如 shenyang)'' AFTER domain', 'SELECT 1');
PREPARE st FROM @sql_alias; EXECUTE st; DEALLOCATE PREPARE st;

SET @has_mode := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='yy_site' AND column_name='mode');
SET @sql_mode := IF(@has_mode=0, 'ALTER TABLE yy_site ADD COLUMN mode varchar(10) NOT NULL DEFAULT ''domain'' COMMENT ''domain=域名模式 path=路径模式'' AFTER alias', 'SELECT 1');
PREPARE st FROM @sql_mode; EXECUTE st; DEALLOCATE PREPARE st;
