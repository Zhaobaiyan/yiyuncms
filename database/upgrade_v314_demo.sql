-- =====================================================================
-- 易云CMS v3.14 演示数据脚本（newcms.com 全功能展示）
-- 幂等：固定 ID 段（内容 200-215 / 评论 100+ / 收藏 100+ / 购物车 100+ / 订单 100+）
-- 可重复执行；执行后前台可见：产品价格、评论、收藏、购物车、订单、留言等
-- =====================================================================

-- 0) 清理旧演示数据（幂等）
DELETE FROM yy_cn_content WHERE id BETWEEN 200 AND 215;
DELETE FROM yy_shop WHERE conid BETWEEN 200 AND 215;
DELETE FROM yy_cn_comment WHERE id >= 100;
DELETE FROM yy_cn_favorite WHERE id >= 100;
DELETE FROM yy_cart WHERE id >= 100;
DELETE FROM yy_order WHERE id >= 100;
DELETE FROM yy_order_product WHERE order_id >= 100;

-- 1) 产品内容（产品中心 72 人造草坪 / 73 塑料跑道 / 74 运动地板 / 75 体育设备 / 76 体育用品）
-- 图片素材来自演示库已有 upload 图片
SET @t = UNIX_TIMESTAMP() - 86400*5;

INSERT INTO yy_cn_content (id,catid,model_id,title,etitle,jumpurl,short_title,author,source,thumb,image,keywords,tag,description,content,extend,is_top,is_recommend,is_hot,status,views,sort,create_time,add_time,update_time,publish_time,author_uid,audit_uid,audit_status,submit_count,delete_time) VALUES
(200,72,1,'免填充人造草坪 FX-50','','','人造草坪','易云CMS','易云CMS','/upload/image/20260720/34581df9a568320f968eeb086544f615.jpg','/upload/image/20260720/34581df9a568320f968eeb086544f615.jpg','人造草坪,免填充,运动草','草坪','50mm 免填充人造草坪，适合足球场、学校操场，环保耐用。','<p>FX-50 免填充人造草坪采用高品质 PP/PE 草丝，高度 50mm，无需石英砂与橡胶颗粒填充，施工更快捷、维护更简单。</p><p>产品特点：</p><ul><li>草丝耐磨、抗紫外线，使用寿命长</li><li>排水性能优异，雨后快速可用</li><li>环保材料，通过 SGS 检测</li></ul>','{"piclist":["/upload/image/20260720/34581df9a568320f968eeb086544f615.jpg","/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg","/upload/image/20260722/2c7129b43d321f0538ca43012b9e5d4e.jpg"]}',0,1,1,1,82,100,@t,@t,@t,@t,1,1,0,0,0),
(201,72,1,'直铺式人造草坪 30mm 运动系列','','','人造草坪','易云CMS','易云CMS','/upload/image/20260720/33ea2b015a834ff63370df17793285e4.jpg','/upload/image/20260720/33ea2b015a834ff63370df17793285e4.jpg','人造草坪,直铺,运动','草坪','30mm 直铺式人造草坪，适用于幼儿园、笼式足球场。','<p>30mm 直铺式人造草坪性价比高，施工简单，适合幼儿园活动场地、笼式足球场、屋顶球场等场景。</p><p>采用加筋背胶工艺，接缝牢固不易开裂。</p>','{"piclist":["/upload/image/20260720/33ea2b015a834ff63370df17793285e4.jpg","/upload/image/20260720/3c06eca08bc7b9c3de8cfeff6e469211.jpg","/upload/image/20260720/5126a25a9109f4f9b988965dee8c5734.jpg"]}',0,1,0,1,64,90,@t-86400,@t-86400,@t-86400,@t-86400,1,1,0,0,0),
(202,73,1,'混合型塑胶跑道 13mm','','','塑胶跑道','易云CMS','易云CMS','/upload/image/20260720/65a9e2bcc374836ff95a011dc7ad7ddb.jpg','/upload/image/20260720/65a9e2bcc374836ff95a011dc7ad7ddb.jpg','塑胶跑道,混合型,田径场','跑道','13mm 混合型塑胶跑道，国际田联认证，专业田径场首选。','<p>混合型塑胶跑道面层由 PU 胶与橡胶颗粒混合摊铺而成，弹性好、耐候性强，符合国际田联 IAAF 标准。</p><p>适用于标准田径场、体育中心训练场。</p>','{"piclist":["/upload/image/20260720/65a9e2bcc374836ff95a011dc7ad7ddb.jpg","/upload/image/20260720/7ab2e61419e16bc699bb301dafdd2da4.jpg","/upload/image/20260720/a28e08d0ce02f79b12924bc05bc52e25.jpg"]}',1,1,1,1,128,80,@t-2*86400,@t-2*86400,@t-2*86400,@t-2*86400,1,1,0,0,0),
(203,73,1,'透气型塑胶跑道 13mm','','','塑胶跑道','易云CMS','易云CMS','/upload/image/20260720/7ab2e61419e16bc699bb301dafdd2da4.jpg','/upload/image/20260720/7ab2e61419e16bc699bb301dafdd2da4.jpg','塑胶跑道,透气型,操场','跑道','透气型塑胶跑道，透气透水，造价适中，学校操场常用。','<p>透气型塑胶跑道采用机械喷涂工艺，面层透气透水，雨天不积水，性价比高，广泛用于学校操场、社区健身步道。</p>','{"piclist":["/upload/image/20260720/7ab2e61419e16bc699bb301dafdd2da4.jpg","/upload/image/20260720/33ea2b015a834ff63370df17793285e4.jpg"]}',0,0,1,1,41,70,@t-3*86400,@t-3*86400,@t-3*86400,@t-3*86400,1,1,0,0,0),
(204,74,1,'悬浮式拼装运动地板','','','运动地板','易云CMS','易云CMS','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','运动地板,悬浮拼装,篮球场','地板','悬浮式拼装运动地板，篮球场、羽毛球场、幼儿园专用。','<p>悬浮式拼装地板采用改性 PP 材料，模块化拼装、锁扣连接，安装无需胶水，可反复拆装迁移。</p><p>弹性缓冲性能好，保护运动者关节，色彩丰富可定制场地分区。</p>','{"piclist":["/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg","/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg"]}',0,1,1,1,96,60,@t-4*86400,@t-4*86400,@t-4*86400,@t-4*86400,1,1,0,0,0),
(205,74,1,'PVC 运动地板 4.5mm','','','运动地板','易云CMS','易云CMS','/upload/image/20260720/5126a25a9109f4f9b988965dee8c5734.jpg','/upload/image/20260720/5126a25a9109f4f9b988965dee8c5734.jpg','PVC地板,羽毛球,运动','地板','4.5mm PVC 运动地板，防滑耐磨，羽毛球馆、乒乓球馆专用。','<p>PVC 运动地板具有优异耐磨层与发泡缓冲层，脚感舒适，防滑性能好，广泛用于羽毛球馆、乒乓球馆、健身房等室内运动空间。</p>','{"piclist":["/upload/image/20260720/5126a25a9109f4f9b988965dee8c5734.jpg","/upload/image/20260720/34581df9a568320f968eeb086544f615.jpg"]}',0,1,0,1,73,50,@t-5*86400,@t-5*86400,@t-5*86400,@t-5*86400,1,1,0,0,0),
(206,75,1,'电动液压篮球架','','','体育设备','易云CMS','易云CMS','/upload/image/20260720/a28e08d0ce02f79b12924bc05bc52e25.jpg','/upload/image/20260720/a28e08d0ce02f79b12924bc05bc52e25.jpg','篮球架,电动液压,体育设备','设备','电动液压篮球架，升降平稳，赛事级标准。','<p>电动液压篮球架采用液压升降系统，操作平稳，篮板采用钢化玻璃，符合 FIBA 赛事标准，适用于大型体育场馆。</p>','{"piclist":["/upload/image/20260720/a28e08d0ce02f79b12924bc05bc52e25.jpg","/upload/image/20260720/65a9e2bcc374836ff95a011dc7ad7ddb.jpg"]}',1,1,1,1,156,40,@t-6*86400,@t-6*86400,@t-6*86400,@t-6*86400,1,1,0,0,0),
(207,75,1,'移动式羽毛球柱','','','体育设备','易云CMS','易云CMS','/upload/image/20260720/3c06eca08bc7b9c3de8cfeff6e469211.jpg','/upload/image/20260720/3c06eca08bc7b9c3de8cfeff6e469211.jpg','羽毛球柱,移动式,体育器材','设备','移动式羽毛球柱，配网+手摇升降，便携耐用。','<p>移动式羽毛球柱采用加粗钢管与手摇升降装置，配专业比赛球网，底座可配重稳定，适合室内外羽毛球场地。</p>','{"piclist":["/upload/image/20260720/3c06eca08bc7b9c3de8cfeff6e469211.jpg","/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg"]}',0,0,1,1,38,30,@t-7*86400,@t-7*86400,@t-7*86400,@t-7*86400,1,1,0,0,0),
(208,76,1,'赛事级足球门 7.32m','','','体育用品','易云CMS','易云CMS','/upload/image/20260720/33ea2b015a834ff63370df17793285e4.jpg','/upload/image/20260720/33ea2b015a834ff63370df17793285e4.jpg','足球门,赛事级,球门','用品','7.32m 赛事级足球门，铝合金管材，配网含防翻装置。','<p>赛事级足球门采用高强度铝合金管材，静电喷涂防锈，配专业足球网与地面防翻装置，符合国际比赛标准。</p>','{"piclist":["/upload/image/20260720/33ea2b015a834ff63370df17793285e4.jpg","/upload/image/20260720/34581df9a568320f968eeb086544f615.jpg"]}',0,1,0,1,52,20,@t-8*86400,@t-8*86400,@t-8*86400,@t-8*86400,1,1,0,0,0),
(209,76,1,'专业比赛乒乓球台','','','体育用品','易云CMS','易云CMS','/upload/image/20260720/5126a25a9109f4f9b988965dee8c5734.jpg','/upload/image/20260720/5126a25a9109f4f9b988965dee8c5734.jpg','乒乓球台,比赛级,体育用品','用品','ITTF 认证比赛乒乓球台，25mm 加厚面板。','<p>专业比赛乒乓球台通过 ITTF 认证，25mm 高密度面板弹性均匀，金属框架稳固，适合专业训练与比赛。</p>','{"piclist":["/upload/image/20260720/5126a25a9109f4f9b988965dee8c5734.jpg","/upload/image/20260720/a28e08d0ce02f79b12924bc05bc52e25.jpg"]}',0,1,1,1,67,10,@t-9*86400,@t-9*86400,@t-9*86400,@t-9*86400,1,1,0,0,0),
(210,69,1,'行业动态演示文章：运动场地验收标准指南','','','行业动态','易云CMS','易云CMS','/upload/image/20260720/65a9e2bcc374836ff95a011dc7ad7ddb.jpg','/upload/image/20260720/65a9e2bcc374836ff95a011dc7ad7ddb.jpg','验收标准,运动场地','行业动态','运动场地验收的关键指标与流程说明。','<p>本文为演示数据示例，介绍运动场地工程的验收标准、关键指标与常见问题。</p>','{"piclist":[]}',0,1,0,1,45,10,@t-3*86400,@t-3*86400,@t-3*86400,@t-3*86400,1,1,0,0,0),
(211,69,1,'行业动态演示文章：人造草坪维护保养手册','','','行业动态','易云CMS','易云CMS','/upload/image/20260720/7ab2e61419e16bc699bb301dafdd2da4.jpg','/upload/image/20260720/7ab2e61419e16bc699bb301dafdd2da4.jpg','人造草坪,保养,维护','行业动态','人造草坪的日常维护与保养要点。','<p>本文为演示数据示例，介绍人造草坪的日常维护、清洁与翻新要点。</p>','{"piclist":[]}',0,0,1,1,33,9,@t-5*86400,@t-5*86400,@t-5*86400,@t-5*86400,1,1,0,0,0);

-- 2) 商品价格（yy_shop：conid -> price / original_price / stock / sales / status）
INSERT INTO yy_shop (lang,conid,price,original_price,stock,sales,status) VALUES
('cn',200,68.00,88.00,500,126,1),
('cn',201,45.00,58.00,800,210,1),
('cn',202,128.00,158.00,300,86,1),
('cn',203,98.00,118.00,400,73,1),
('cn',204,36.00,46.00,1000,352,1),
('cn',205,58.00,72.00,600,118,1),
('cn',206,18800.00,22000.00,20,4,1),
('cn',207,680.00,860.00,150,31,1),
('cn',208,3200.00,3800.00,60,12,1),
('cn',209,2800.00,3200.00,80,16,1);

-- 3) 评论（已通过 status=1，含后台回复 reply；覆盖 id 100-112）
INSERT INTO yy_cn_comment (id,content_id,catid,member_id,username,email,content,reply,reply_time,status,ip,create_time,update_time) VALUES
(100,200,72,4,'testshop1','','质量非常好，场地铺完效果很棒！','感谢您的认可，欢迎再来选购！',UNIX_TIMESTAMP()-86400,1,'127.0.0.1',UNIX_TIMESTAMP()-86400*2,UNIX_TIMESTAMP()-86400),
(101,200,72,1,'ceshi01','','物流很快，客服态度也很好。','谢谢支持！',UNIX_TIMESTAMP()-86400,1,'127.0.0.1',UNIX_TIMESTAMP()-86400*3,UNIX_TIMESTAMP()-86400),
(102,202,73,4,'testshop1','','跑道弹性很好，孩子们很喜欢。','',0,1,'127.0.0.1',UNIX_TIMESTAMP()-86400*2,UNIX_TIMESTAMP()-86400*2),
(103,202,73,2,'ceshi02','','施工队很专业，验收一次通过。','感谢信任！',UNIX_TIMESTAMP()-86400,1,'127.0.0.1',UNIX_TIMESTAMP()-86400*4,UNIX_TIMESTAMP()-86400),
(104,204,74,1,'ceshi01','','拼装地板颜色好看，脚感舒适。','',0,1,'127.0.0.1',UNIX_TIMESTAMP()-86400*3,UNIX_TIMESTAMP()-86400*3),
(105,206,75,4,'testshop1','','篮球架质量杠杠的，升降很平稳。','感谢支持！',UNIX_TIMESTAMP()-86400,1,'127.0.0.1',UNIX_TIMESTAMP()-86400*5,UNIX_TIMESTAMP()-86400),
(106,106,86,2,'ceshi02','','浑河西峡谷项目做得很漂亮，点赞！','感谢您的认可！',UNIX_TIMESTAMP()-86400,1,'127.0.0.1',UNIX_TIMESTAMP()-86400*6,UNIX_TIMESTAMP()-86400),
(107,208,76,4,'testshop1','','足球门很结实，配网也标准。','',0,1,'127.0.0.1',UNIX_TIMESTAMP()-86400*4,UNIX_TIMESTAMP()-86400*4),
(108,209,76,1,'ceshi01','','乒乓球台弹性均匀，比赛体验很好。','谢谢！',UNIX_TIMESTAMP()-86400,1,'127.0.0.1',UNIX_TIMESTAMP()-86400*5,UNIX_TIMESTAMP()-86400),
(109,203,73,3,'1912323894','','性价比很高的跑道，推荐。','',0,1,'127.0.0.1',UNIX_TIMESTAMP()-86400*6,UNIX_TIMESTAMP()-86400*6),
(110,201,72,2,'ceshi02','','幼儿园场地用的就是这款，很耐用。','感谢信任！',UNIX_TIMESTAMP()-86400,1,'127.0.0.1',UNIX_TIMESTAMP()-86400*7,UNIX_TIMESTAMP()-86400),
(111,205,74,3,'1912323894','','防滑效果不错，打球放心。','',0,1,'127.0.0.1',UNIX_TIMESTAMP()-86400*8,UNIX_TIMESTAMP()-86400*8),
(112,207,75,4,'testshop1','','羽毛球柱升降方便，收纳也简单。','',0,1,'127.0.0.1',UNIX_TIMESTAMP()-86400*9,UNIX_TIMESTAMP()-86400*9);

-- 4) 收藏（演示会员收藏内容）
INSERT INTO yy_cn_favorite (id,member_id,content_id,create_time) VALUES
(100,'4',200,UNIX_TIMESTAMP()-86400),
(101,'4',202,UNIX_TIMESTAMP()-86400*2),
(102,'4',206,UNIX_TIMESTAMP()-86400*3),
(103,'1',106,UNIX_TIMESTAMP()-86400*4);

-- 5) 购物车预置（会员4 testshop1 已登录时可见）
INSERT INTO yy_cart (id,member_id,lang,conid,num,create_time) VALUES
(100,4,'cn',200,2,UNIX_TIMESTAMP()-86400),
(101,4,'cn',206,1,UNIX_TIMESTAMP()-86400*2);

-- 6) 补充订单：待付款（线下转账演示）+ 已完成
INSERT INTO yy_order (id,order_no,member_id,lang,total_amount,pay_amount,status,pay_type,pay_time,name,mobile,address,remark,create_time,update_time) VALUES
(100,'202610101800001000',4,'cn',136.00,136.00,0,'offline',0,'测试商城','13800000000','沈阳市浑南区测试地址','线下支付演示订单',UNIX_TIMESTAMP()-86400,UNIX_TIMESTAMP()-86400),
(101,'202610101900001001',4,'cn',680.00,680.00,1,'offline',UNIX_TIMESTAMP()-86400*2,'测试商城','13800000000','沈阳市浑南区测试地址','已完成线下订单',UNIX_TIMESTAMP()-86400*3,UNIX_TIMESTAMP()-86400*2);

INSERT INTO yy_order_product (order_id,conid,title,pic,price,num) VALUES
(100,200,'免填充人造草坪 FX-50','/upload/image/20260720/34581df9a568320f968eeb086544f615.jpg',68.00,2),
(101,207,'移动式羽毛球柱','/upload/image/20260720/3c06eca08bc7b9c3de8cfeff6e469211.jpg',680.00,1);
