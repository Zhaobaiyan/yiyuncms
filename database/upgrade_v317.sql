-- ============================================================
-- 易云CMS v3.17 升级脚本：真实支付商户号配置键（幂等）
-- 说明：SKU 规格级价格库存不新增数据表——复用 yy_shop.specs
--       JSON 的 _sku 键存储 [{"spec":"颜色:红,尺寸:X","price":199,"stock":50}]
-- 适用：已有数据库执行本文件；全新安装 install.sql 已含以下配置默认值
-- ============================================================
INSERT INTO `yy_config` (`name`,`value`,`create_time`,`update_time`) VALUES
('shop.pay_mode','1',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('shop.wx_mchid','',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('shop.wx_appid','',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('shop.wx_key','',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('shop.wx_cert_path','',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('shop.wx_key_path','',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('shop.ali_appid','',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('shop.ali_private_key','',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('shop.ali_public_key','',UNIX_TIMESTAMP(),UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE `name`=`name`;
