-- =============================================================
-- 易云CMS v3.16 升级脚本：系统功能总开关
-- 会员系统 / 商城系统 / 客服系统 / 评论 / 收藏
-- 关闭后：后台对应菜单隐藏，前台对应页面禁止访问（功能未开启提示）
-- 幂等：可重复执行
-- =============================================================

INSERT INTO yy_config (name,value,create_time,update_time) VALUES
('member.open','1',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('shop.open','1',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('service.open','1',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('comment.open','1',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('favorite.open','1',UNIX_TIMESTAMP(),UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE update_time=VALUES(update_time);

-- 说明：
-- member.open    会员系统开关（1开0关）：前台 /member/* 全部禁止访问；后台 会员管理/会员设置 菜单隐藏
-- shop.open      商城系统开关（1开0关）：前台 /shop/* 全部禁止访问；后台 商城管理 整组隐藏（商品价格/订单/售后/钱包/支付设置）
-- service.open   客服系统开关（1开0关）：前台 /service/* 禁止访问；后台 客服会话 菜单隐藏
-- comment.open   评论开关（1开0关）：前台详情页不注入评论数据、提交评论接口拒绝；后台 评论管理 菜单隐藏
-- favorite.open  收藏开关（1开0关）：前台详情页收藏按钮隐藏、收藏接口拒绝
