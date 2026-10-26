-- ============================================================
-- 易云CMS 数据库清洗脚本（针对临时库 yy_clean 执行）
-- 目标：生成干净的安装包数据库（install.sql）
-- ============================================================

-- ========== 1. 清空测试内容 / 日志 / 浏览记录 ==========
TRUNCATE yy_cn_content;
TRUNCATE yy_cn_content_audit_log;
TRUNCATE yy_cn_message;
TRUNCATE yy_en_content;
TRUNCATE yy_en_content_audit_log;
TRUNCATE yy_en_message;
TRUNCATE yy_admin_log;
TRUNCATE yy_visit_log;
TRUNCATE yy_member;
TRUNCATE yy_cn_ad;
TRUNCATE yy_cn_link;
TRUNCATE yy_en_ad;
TRUNCATE yy_en_link;

-- ========== 2. 管理员只留超级管理员 ==========
DELETE FROM yy_admin WHERE id <> 1;
DELETE FROM yy_admin_role WHERE id <> 1;

-- ========== 3. 内容模型只留文章模型，栏目模型只留默认 ==========
DELETE FROM yy_cn_model WHERE id <> 1;
DELETE FROM yy_cn_model_field;
DELETE FROM yy_en_model WHERE id <> 1;
DELETE FROM yy_en_model_field;
DELETE FROM yy_cat_model WHERE id <> 1;
DELETE FROM yy_cat_model_field WHERE model_id <> 1;

-- ========== 4. 栏目重建（干净演示栏目，ID 从 1 开始） ==========
TRUNCATE yy_cn_category;
INSERT INTO yy_cn_category (id,parent_id,model_id,name,ename,type,seo_title,description,content,template,content_template,sort,is_show,is_nav,create_time,update_time) VALUES
(1,0,1,'公司简介','aboutus','single','公司简介','','<p>这里是公司简介内容，请在后台「栏目管理 - 公司简介 - 内容」中编辑。</p>','list_page','',1,1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
(2,0,1,'新闻中心','news','list','新闻中心','','','list_article','show_article',2,1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
(3,0,1,'产品中心','product','list','产品中心','','','list_product','show_product',3,1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
(4,0,1,'联系我们','contactus','single','联系我们','','<p>这里是联系我们内容，请在后台编辑。</p>','list_contact','',4,1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
(5,0,1,'在线留言','message','single','在线留言','','','list_message','',5,1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP());

TRUNCATE yy_en_category;
INSERT INTO yy_en_category (id,parent_id,model_id,name,ename,type,seo_title,description,content,template,content_template,sort,is_show,is_nav,create_time,update_time) VALUES
(1,0,1,'About Us','aboutus','single','About Us','','<p>About us content, please edit in admin.</p>','list_page','',1,1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
(2,0,1,'News','news','list','News','','','list_article','show_article',2,1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
(3,0,1,'Products','product','list','Products','','','list_product','show_product',3,1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
(4,0,1,'Contact','contactus','single','Contact','','<p>Contact content, please edit in admin.</p>','list_contact','',4,1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
(5,0,1,'Message','message','single','Message','','','list_message','',5,1,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP());

-- ========== 5. 演示内容（新闻 2 篇 + 产品 2 篇，可删除） ==========
INSERT INTO yy_cn_content (catid,model_id,title,author,description,content,status,views,sort,create_time,add_time,update_time) VALUES
(2,1,'欢迎使用易云CMS','管理员','这是一篇示例文章，用于演示易云CMS 的内容管理功能。','<p>易云CMS 是一套基于 ThinkPHP 6 开发的现代化企业建站内容管理系统，支持多语言、多内容模型、自定义字段、栏目模型、静态页面生成、角色权限、操作日志、浏览统计等丰富功能。</p>',1,10,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
(2,1,'易云CMS 功能简介','管理员','栏目管理、内容管理、留言板、自定义块、模板管理、多语言等。','<p>后台内置栏目管理、内容管理、留言板、自定义块、广告位、友情链接、模板管理、静态生成、多语言管理、角色权限、投稿审核、操作日志、浏览统计、七牛云存储等功能。</p>',1,8,2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
(3,1,'示例产品 A','管理员','示例产品 A 的简介。','<p>这是示例产品 A 的内容，请在后台「内容管理」中编辑替换。</p>',1,5,1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
(3,1,'示例产品 B','管理员','示例产品 B 的简介。','<p>这是示例产品 B 的内容，请在后台「内容管理」中编辑替换。</p>',1,3,2,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP());

-- ========== 6. 网站设置重置为「易云CMS」 ==========
DELETE FROM yy_cn_setting;
INSERT INTO yy_cn_setting (name,value) VALUES
('site.name','易云CMS'),
('site.url','http://localhost'),
('site.logo',''),
('site.icp',''),
('site.keywords','易云CMS,企业建站,内容管理系统'),
('site.description','易云CMS 是一套基于 ThinkPHP 6 的现代化企业建站内容管理系统。'),
('site.copyright','Copyright © 2026 易云CMS All Rights Reserved.'),
('site.close','0'),
('site.close_tip','站点维护中，请稍后再访问'),
('seo.title','易云CMS - 让建站更简单'),
('seo.keywords','易云CMS,企业建站,内容管理系统'),
('seo.description','易云CMS 企业建站内容管理系统'),
('message.audit','1'),
('message.notify','0');

DELETE FROM yy_en_setting;
INSERT INTO yy_en_setting (name,value) VALUES
('site.name','Yiyun CMS'),
('site.url','http://localhost'),
('site.logo',''),
('site.icp',''),
('site.keywords','Yiyun CMS,Content Management System'),
('site.description','Yiyun CMS is a modern enterprise website CMS based on ThinkPHP 6.'),
('site.copyright','Copyright © 2026 Yiyun CMS All Rights Reserved.'),
('site.close','0'),
('site.close_tip','Site under maintenance, please visit later.'),
('seo.title','Yiyun CMS - Build Website Easier'),
('seo.keywords','Yiyun CMS,Enterprise Website'),
('seo.description','Yiyun CMS Enterprise Website CMS'),
('message.audit','1'),
('message.notify','0');
