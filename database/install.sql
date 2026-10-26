-- MySQL dump 10.13  Distrib 5.7.26, for Win64 (x86_64)
--
-- Host: localhost    Database: yy_clean
-- ------------------------------------------------------
-- Server version	5.7.26

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `yy_admin`
--

DROP TABLE IF EXISTS `yy_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_admin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '登录账号',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码(哈希)',
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '头像',
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin' COMMENT '角色 admin/super',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 1启用 0禁用',
  `last_login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `last_login_ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `login_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登录次数',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台管理员';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_admin`
--

LOCK TABLES `yy_admin` WRITE;
/*!40000 ALTER TABLE `yy_admin` DISABLE KEYS */;
INSERT INTO `yy_admin` VALUES (1,'admin','$2y$10$4GS6O8EYrQ0StZDshv8Y5uwCMIUti5MEtDJhmQG.6D1H3iH83q8PO','超级管理员','','super',1,1791533457,'127.0.0.1',33,1790238137,1791533457);
/*!40000 ALTER TABLE `yy_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_admin_log`
--

DROP TABLE IF EXISTS `yy_admin_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_admin_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '动作',
  `remark` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_ctime` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台日志';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_admin_log`
--

LOCK TABLES `yy_admin_log` WRITE;
/*!40000 ALTER TABLE `yy_admin_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_admin_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_admin_role`
--

DROP TABLE IF EXISTS `yy_admin_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_admin_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '角色标识(唯一)',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '角色名称',
  `rules` text COMMENT '允许控制器白名单JSON(* 或 ["content",...])',
  `cat_ids` text COMMENT '可管理栏目ID(JSON按语言分组: {"cn":[1,2],"en":[]})',
  `board_ids` text COMMENT '可查看留言板ID(JSON按语言分组)',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `is_system` tinyint(1) NOT NULL DEFAULT '0' COMMENT '内置角色不可删除',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `sort` int(11) NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `need_audit` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1=发布内容需审核（默认草稿）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COMMENT='后台管理员角色';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_admin_role`
--

LOCK TABLES `yy_admin_role` WRITE;
/*!40000 ALTER TABLE `yy_admin_role` DISABLE KEYS */;
INSERT INTO `yy_admin_role` VALUES (1,'super','超级管理员','*',NULL,NULL,'拥有全部权限',1,1,0,0,0,0);
/*!40000 ALTER TABLE `yy_admin_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_cat_model`
--

DROP TABLE IF EXISTS `yy_cat_model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_cat_model` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '妯″瀷鏍囪瘑',
  `title` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '妯″瀷鍚嶇О',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='鏍忕洰妯″瀷(鏍忕洰瀛楁妯℃澘)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cat_model`
--

LOCK TABLES `yy_cat_model` WRITE;
/*!40000 ALTER TABLE `yy_cat_model` DISABLE KEYS */;
INSERT INTO `yy_cat_model` VALUES (1,'default','默认栏目模型',1791452132,1791452132);
/*!40000 ALTER TABLE `yy_cat_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_cat_model_field`
--

DROP TABLE IF EXISTS `yy_cat_model_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_cat_model_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '鎵€灞炴爮鐩ā鍨婭D',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '瀛楁鏍囪瘑',
  `title` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '瀛楁鍚嶇О',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT '绫诲瀷 text/image/textarea/richtext/select/radio/checkbox',
  `options` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '閫夐」(鑻辨枃閫楀彿鍒嗛殧)',
  `sort` int(11) NOT NULL DEFAULT '100',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_model` (`model_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='鏍忕洰妯″瀷瀛楁';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cat_model_field`
--

LOCK TABLES `yy_cat_model_field` WRITE;
/*!40000 ALTER TABLE `yy_cat_model_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_cat_model_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_cn_ad`
--

DROP TABLE IF EXISTS `yy_cn_ad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_cn_ad` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '广告位标识(标签用)',
  `title` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '广告位名称',
  `width` int(11) NOT NULL DEFAULT '0' COMMENT '宽',
  `height` int(11) NOT NULL DEFAULT '0' COMMENT '高',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '广告代码(HTML/JSON)',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT '100',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告位-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_ad`
--

LOCK TABLES `yy_cn_ad` WRITE;
/*!40000 ALTER TABLE `yy_cn_ad` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_cn_ad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_cn_block`
--

DROP TABLE IF EXISTS `yy_cn_block`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_cn_block` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标识(英文，标签用)',
  `title` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT '类型 text/html/image',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '内容',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图片',
  `link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '链接',
  `sort` int(11) NOT NULL DEFAULT '100',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='自定义块-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_block`
--

LOCK TABLES `yy_cn_block` WRITE;
/*!40000 ALTER TABLE `yy_cn_block` DISABLE KEYS */;
INSERT INTO `yy_cn_block` VALUES (21,0,'tel','电话','text','15242413419','','',100,1,1791461938,1791461938),(22,0,'mobile','手机','text','15242413419','','',100,1,1791461938,1791461938),(23,0,'email','邮箱','text','88888888@qq.com','','',100,1,1791461938,1791461938),(24,0,'address','地址','text','辽宁省沈阳市铁西区飞翔路18号','','',100,1,1791461938,1791461938),(25,0,'ewm','微信二维码','image','','/upload/image/20260720/b95a531f5ec55fe302bc8cde6f6d0284.png','',100,1,1791461938,1791461938),(26,0,'flogo','底部logo','image','','','',100,1,1791461938,1791461938),(27,0,'bdsq-code','百度商桥代码','text','','','',100,1,1791461938,1791461938),(28,0,'js-code','第三方JS代码','text','','','',100,1,1791461938,1791461938),(29,0,'tj-code','网站统计代码','text','','','',100,1,1791461938,1791461938),(30,0,'sjewm','手机二维码','image','','/upload/image/20260720/b95a531f5ec55fe302bc8cde6f6d0284.png','',100,1,1791461938,1791461938),(31,0,'lxr','联系人','text','王先生','','',100,1,1791461938,1791461938),(32,0,'wz','网址','text','www.seqill.cn','','',100,1,1791461938,1791461938);
/*!40000 ALTER TABLE `yy_cn_block` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_cn_category`
--

DROP TABLE IF EXISTS `yy_cn_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_cn_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父栏目ID 0为顶级',
  `model_id` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '内容模型ID',
  `cat_model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '鏍忕洰妯″瀷ID(鏍忕洰瀛楁妯℃澘)',
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '栏目名称',
  `ename` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '英文别名/目录名(URL用)',
  `subtitle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'list' COMMENT '类型 list列表 single单页 external外链 redirect跳转',
  `cover` tinyint(1) NOT NULL DEFAULT '0',
  `external_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '外链地址(type=external)或跳转目标(@id/@ename/http,type=redirect)',
  `target` tinyint(1) NOT NULL DEFAULT '0',
  `seo_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `keywords` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '栏目图片',
  `content` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '单页内容(type=single)',
  `extend` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '栏目自定义字段JSON',
  `template` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '列表模板(留空用模型默认)',
  `content_template` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '内容页模板(留空用模型默认)',
  `static_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '静态页生成路径',
  `is_show` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否显示 1是 0否',
  `is_nav` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否导航 1是 0否',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序(小在前)',
  `pagesize` int(10) unsigned NOT NULL DEFAULT '10',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_show_sort` (`is_show`,`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='栏目-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_category`
--

LOCK TABLES `yy_cn_category` WRITE;
/*!40000 ALTER TABLE `yy_cn_category` DISABLE KEYS */;
INSERT INTO `yy_cn_category` VALUES (1,0,1,0,'公司简介','aboutus','','single',0,'',0,'公司简介','','','','<p>这里是公司简介内容，请在后台「栏目管理 - 公司简介 - 内容」中编辑。</p>',NULL,'list_page','','',1,1,1,10,1791534391,1791534391),(2,0,1,0,'新闻中心','news','','list',0,'',0,'新闻中心','','','','',NULL,'list_article','show_article','',1,1,2,10,1791534391,1791534391),(3,0,1,0,'产品中心','product','','list',0,'',0,'产品中心','','','','',NULL,'list_product','show_product','',1,1,3,10,1791534391,1791534391),(4,0,1,0,'联系我们','contactus','','single',0,'',0,'联系我们','','','','<p>这里是联系我们内容，请在后台编辑。</p>',NULL,'list_contact','','',1,1,4,10,1791534391,1791534391),(5,0,1,0,'在线留言','message','','single',0,'',0,'在线留言','','','','',NULL,'list_message','','',1,1,5,10,1791534391,1791534391);
/*!40000 ALTER TABLE `yy_cn_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_cn_content`
--

DROP TABLE IF EXISTS `yy_cn_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_cn_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `catid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属栏目ID',
  `model_id` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '内容模型ID',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `etitle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jumpurl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `short_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '短标题',
  `author` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '作者',
  `source` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '来源',
  `thumb` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '缩略图',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '主图',
  `keywords` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关键词',
  `tag` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '摘要',
  `content` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '正文',
  `extend` text COLLATE utf8mb4_unicode_ci COMMENT '扩展字段数据(JSON)',
  `is_top` tinyint(4) NOT NULL DEFAULT '0' COMMENT '置顶',
  `is_recommend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '推荐',
  `is_hot` tinyint(4) NOT NULL DEFAULT '0' COMMENT '热门',
  `top` tinyint(1) NOT NULL DEFAULT '0',
  `istop` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 1发布 0草稿',
  `views` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '浏览量',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
  `static_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '静态页路径',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布时间',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '建立时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `author_uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发稿人管理员ID',
  `audit_uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核人管理员ID',
  `audit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `audit_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '审核状态 0正常 1待审核 2已驳回',
  `submit_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '提交审核次数',
  `reject_reason` text COLLATE utf8mb4_unicode_ci COMMENT '最新驳回原因',
  PRIMARY KEY (`id`),
  KEY `idx_catid` (`catid`),
  KEY `idx_status_sort` (`status`,`sort`,`is_top`),
  KEY `idx_publish` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_content`
--

LOCK TABLES `yy_cn_content` WRITE;
/*!40000 ALTER TABLE `yy_cn_content` DISABLE KEYS */;
INSERT INTO `yy_cn_content` VALUES (1,2,1,'欢迎使用易云CMS','','','','管理员','','','','','','这是一篇示例文章，用于演示易云CMS 的内容管理功能。','<p>易云CMS 是一套基于 ThinkPHP 6 开发的现代化企业建站内容管理系统，支持多语言、多内容模型、自定义字段、栏目模型、静态页面生成、角色权限、操作日志、浏览统计等丰富功能。</p>',NULL,0,0,0,0,0,1,10,1,'',1791534391,1791534391,1791534391,0,0,0,0,0,NULL),(2,2,1,'易云CMS 功能简介','','','','管理员','','','','','','栏目管理、内容管理、留言板、自定义块、模板管理、多语言等。','<p>后台内置栏目管理、内容管理、留言板、自定义块、广告位、友情链接、模板管理、静态生成、多语言管理、角色权限、投稿审核、操作日志、浏览统计、七牛云存储等功能。</p>',NULL,0,0,0,0,0,1,8,2,'',1791534391,1791534391,1791534391,0,0,0,0,0,NULL),(3,3,1,'示例产品 A','','','','管理员','','','','','','示例产品 A 的简介。','<p>这是示例产品 A 的内容，请在后台「内容管理」中编辑替换。</p>',NULL,0,0,0,0,0,1,5,1,'',1791534391,1791534391,1791534391,0,0,0,0,0,NULL),(4,3,1,'示例产品 B','','','','管理员','','','','','','示例产品 B 的简介。','<p>这是示例产品 B 的内容，请在后台「内容管理」中编辑替换。</p>',NULL,0,0,0,0,0,1,3,2,'',1791534391,1791534391,1791534391,0,0,0,0,0,NULL);
/*!40000 ALTER TABLE `yy_cn_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_cn_content_audit_log`
--

DROP TABLE IF EXISTS `yy_cn_content_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_cn_content_audit_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1=提交(含修改说明) 2=驳回(含原因) 3=审核通过',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作人管理员ID',
  `note` text COLLATE utf8mb4_unicode_ci COMMENT '修改说明/驳回原因',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容审核日志-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_content_audit_log`
--

LOCK TABLES `yy_cn_content_audit_log` WRITE;
/*!40000 ALTER TABLE `yy_cn_content_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_cn_content_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_cn_link`
--

DROP TABLE IF EXISTS `yy_cn_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_cn_link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '站名',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '链接',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'LOGO',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '描述',
  `sort` int(11) NOT NULL DEFAULT '100',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友情链接-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_link`
--

LOCK TABLES `yy_cn_link` WRITE;
/*!40000 ALTER TABLE `yy_cn_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_cn_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_cn_message`
--

DROP TABLE IF EXISTS `yy_cn_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_cn_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `board_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '称呼',
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '电话',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '主题',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '留言内容',
  `reply` text COLLATE utf8mb4_unicode_ci COMMENT '后台回复',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP',
  `is_read` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否已读',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0待审核 1已通过 2已拒绝',
  `is_public` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否公开 0否 1是',
  `query_code` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '查询码',
  `reply_time` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `extend` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_ctime` (`create_time`),
  KEY `idx_query_code` (`query_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='留言-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_message`
--

LOCK TABLES `yy_cn_message` WRITE;
/*!40000 ALTER TABLE `yy_cn_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_cn_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_cn_message_board`
--

DROP TABLE IF EXISTS `yy_cn_message_board`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_cn_message_board` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL DEFAULT '',
  `title` varchar(80) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `fields` text,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `captcha` tinyint(1) NOT NULL DEFAULT '0',
  `show_public` tinyint(1) NOT NULL DEFAULT '0' COMMENT '前台是否显示是否公开选择 0否 1是',
  `default_public` tinyint(1) NOT NULL DEFAULT '1' COMMENT '默认公开 0否 1是',
  `sort` int(11) NOT NULL DEFAULT '100',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='留言板-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_message_board`
--

LOCK TABLES `yy_cn_message_board` WRITE;
/*!40000 ALTER TABLE `yy_cn_message_board` DISABLE KEYS */;
INSERT INTO `yy_cn_message_board` VALUES (1,'default','在线留言','','[{\"name\":\"name\",\"label\":\"称呼\",\"type\":\"text\",\"required\":1,\"sort\":1},{\"name\":\"phone\",\"label\":\"联系电话\",\"type\":\"text\",\"required\":0,\"sort\":2},{\"name\":\"email\",\"label\":\"电子邮箱\",\"type\":\"text\",\"required\":0,\"sort\":3},{\"name\":\"title\",\"label\":\"留言主题\",\"type\":\"text\",\"required\":0,\"sort\":4},{\"name\":\"content\",\"label\":\"留言内容\",\"type\":\"textarea\",\"required\":1,\"sort\":5}]',1,0,1,0,100,1790557325,1791448113);
/*!40000 ALTER TABLE `yy_cn_message_board` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_cn_model`
--

DROP TABLE IF EXISTS `yy_cn_model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_cn_model` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型标识 article/product/...',
  `title` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型名称',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型说明',
  `list_template` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '默认列表模板',
  `show_template` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '默认内容页模板',
  `fields` text COLLATE utf8mb4_unicode_ci COMMENT '扩展字段定义(JSON)',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_model`
--

LOCK TABLES `yy_cn_model` WRITE;
/*!40000 ALTER TABLE `yy_cn_model` DISABLE KEYS */;
INSERT INTO `yy_cn_model` VALUES (1,'article','文章模型','通用图文文章','list.html','show.html',NULL,1,1790238137,1790238137);
/*!40000 ALTER TABLE `yy_cn_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_cn_model_field`
--

DROP TABLE IF EXISTS `yy_cn_model_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_cn_model_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `model_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL DEFAULT '',
  `label` varchar(80) NOT NULL DEFAULT '',
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `options` text,
  `required` tinyint(4) NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL DEFAULT '100',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_model` (`model_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COMMENT='模型扩展字段-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_model_field`
--

LOCK TABLES `yy_cn_model_field` WRITE;
/*!40000 ALTER TABLE `yy_cn_model_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_cn_model_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_cn_setting`
--

DROP TABLE IF EXISTS `yy_cn_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_cn_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置名',
  `value` text COLLATE utf8mb4_unicode_ci COMMENT '配置值',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='语言设置-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_setting`
--

LOCK TABLES `yy_cn_setting` WRITE;
/*!40000 ALTER TABLE `yy_cn_setting` DISABLE KEYS */;
INSERT INTO `yy_cn_setting` VALUES (15,'site.name','易云CMS',0,0),(16,'site.url','http://localhost',0,0),(17,'site.logo','',0,0),(18,'site.icp','',0,0),(19,'site.keywords','易云CMS,企业建站,内容管理系统',0,0),(20,'site.description','易云CMS 是一套基于 ThinkPHP 6 的现代化企业建站内容管理系统。',0,0),(21,'site.copyright','Copyright © 2026 易云CMS All Rights Reserved.',0,0),(22,'site.close','0',0,0),(23,'site.close_tip','站点维护中，请稍后再访问',0,0),(24,'seo.title','易云CMS - 让建站更简单',0,0),(25,'seo.keywords','易云CMS,企业建站,内容管理系统',0,0),(26,'seo.description','易云CMS 企业建站内容管理系统',0,0),(27,'message.audit','1',0,0),(28,'message.notify','0',0,0);
/*!40000 ALTER TABLE `yy_cn_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_config`
--

DROP TABLE IF EXISTS `yy_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置名',
  `value` text COLLATE utf8mb4_unicode_ci COMMENT '配置值',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='全局配置';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_config`
--

LOCK TABLES `yy_config` WRITE;
/*!40000 ALTER TABLE `yy_config` DISABLE KEYS */;
INSERT INTO `yy_config` VALUES (1,'base.admin_path','admin',1790238137,1790238137),(2,'base.static_mode','1',1790238137,1790238137),(3,'base.upload_size','2',1790238137,1790238137),(4,'base.upload_type','jpg,png,gif,webp,mp4,pdf,zip,doc',1790238137,1790238137),(6,'wap.open','0',1790238137,1790238137),(7,'wap.auto_redirect','0',1790238137,1790238137),(20,'qiniu.open','0',0,0),(21,'qiniu.access_key','zK2BBj_7B-e1nu5x6IHsF_TQwh_TlQCPtBipzSbh',0,0),(22,'qiniu.secret_key','xb-BOsBVDKBX1y054g080OZDzadC9fDVCQnSFhYS',0,0),(23,'qiniu.bucket','pic02-sq',0,0),(24,'qiniu.domain','https://pic02.sq.seqill.cn/',0,0),(25,'qiniu.zone','z1',0,0),(26,'base.static_url_mode','accurate',0,0),(27,'content.audit','1',1791432957,1791432957),(28,'admin.menu_hidden','[]',0,0),(29,'base.code_hint','1',0,0),(30,'base.session_timeout','60',0,0),(31,'base.page_mode','full',0,0),(32,'base.page_window','7',0,0),(33,'base.page_first_last','1',0,0),(34,'base.page_full_limit','15',0,0);
/*!40000 ALTER TABLE `yy_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_en_ad`
--

DROP TABLE IF EXISTS `yy_en_ad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_en_ad` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '广告位标识(标签用)',
  `title` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '广告位名称',
  `width` int(11) NOT NULL DEFAULT '0' COMMENT '宽',
  `height` int(11) NOT NULL DEFAULT '0' COMMENT '高',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '广告代码(HTML/JSON)',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT '100',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告位-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_ad`
--

LOCK TABLES `yy_en_ad` WRITE;
/*!40000 ALTER TABLE `yy_en_ad` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_en_ad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_en_block`
--

DROP TABLE IF EXISTS `yy_en_block`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_en_block` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标识(英文，标签用)',
  `title` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text' COMMENT '类型 text/html/image',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '内容',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '图片',
  `link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '链接',
  `sort` int(11) NOT NULL DEFAULT '100',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='自定义块-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_block`
--

LOCK TABLES `yy_en_block` WRITE;
/*!40000 ALTER TABLE `yy_en_block` DISABLE KEYS */;
INSERT INTO `yy_en_block` VALUES (1,0,'header_logo','Top Logo','text','YouYun CMS','','',1,1,1790238137,1790238137),(2,0,'footer_about','About Footer','text','YouYun CMS is a modern enterprise website CMS built on ThinkPHP 6.','','',2,1,1790238137,1790238137),(3,0,'footer_contact','Contact Footer','html','<p>Hotline: 400-000-0000</p><p>Email: contact@example.com</p>','','',3,1,1790238137,1790238137),(4,0,'home','Home','text',NULL,'','',1,1,0,0),(5,0,'top','Top','text',NULL,'','',2,1,0,0),(6,0,'bottom','Bottom','text',NULL,'','',3,1,0,0),(7,0,'contact','Contact','text',NULL,'','',4,1,0,0),(8,0,'inner','Inner','text',NULL,'','',5,1,0,0);
/*!40000 ALTER TABLE `yy_en_block` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_en_category`
--

DROP TABLE IF EXISTS `yy_en_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_en_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父栏目ID 0为顶级',
  `model_id` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '内容模型ID',
  `cat_model_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '鏍忕洰妯″瀷ID(鏍忕洰瀛楁妯℃澘)',
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '栏目名称',
  `ename` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '英文别名/目录名(URL用)',
  `subtitle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'list' COMMENT '类型 list列表 single单页 external外链 redirect跳转',
  `cover` tinyint(1) NOT NULL DEFAULT '0',
  `external_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '外链地址(type=external)或跳转目标(@id/@ename/http,type=redirect)',
  `target` tinyint(1) NOT NULL DEFAULT '0',
  `seo_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `keywords` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'SEO描述',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '栏目图片',
  `content` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '单页内容(type=single)',
  `extend` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '栏目自定义字段JSON',
  `template` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '列表模板(留空用模型默认)',
  `content_template` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '内容页模板(留空用模型默认)',
  `static_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '静态页生成路径',
  `is_show` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否显示 1是 0否',
  `is_nav` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否导航 1是 0否',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序(小在前)',
  `pagesize` int(10) unsigned NOT NULL DEFAULT '10',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_show_sort` (`is_show`,`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='栏目-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_category`
--

LOCK TABLES `yy_en_category` WRITE;
/*!40000 ALTER TABLE `yy_en_category` DISABLE KEYS */;
INSERT INTO `yy_en_category` VALUES (1,0,1,0,'About Us','aboutus','','single',0,'',0,'About Us','','','','<p>About us content, please edit in admin.</p>',NULL,'list_page','','',1,1,1,10,1791534391,1791534391),(2,0,1,0,'News','news','','list',0,'',0,'News','','','','',NULL,'list_article','show_article','',1,1,2,10,1791534391,1791534391),(3,0,1,0,'Products','product','','list',0,'',0,'Products','','','','',NULL,'list_product','show_product','',1,1,3,10,1791534391,1791534391),(4,0,1,0,'Contact','contactus','','single',0,'',0,'Contact','','','','<p>Contact content, please edit in admin.</p>',NULL,'list_contact','','',1,1,4,10,1791534391,1791534391),(5,0,1,0,'Message','message','','single',0,'',0,'Message','','','','',NULL,'list_message','','',1,1,5,10,1791534391,1791534391);
/*!40000 ALTER TABLE `yy_en_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_en_content`
--

DROP TABLE IF EXISTS `yy_en_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_en_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `catid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属栏目ID',
  `model_id` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '内容模型ID',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `etitle` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `jumpurl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `short_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '短标题',
  `author` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '作者',
  `source` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '来源',
  `thumb` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '缩略图',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '主图',
  `keywords` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '关键词',
  `tag` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '摘要',
  `content` mediumtext COLLATE utf8mb4_unicode_ci COMMENT '正文',
  `extend` text COLLATE utf8mb4_unicode_ci COMMENT '扩展字段数据(JSON)',
  `is_top` tinyint(4) NOT NULL DEFAULT '0' COMMENT '置顶',
  `is_recommend` tinyint(4) NOT NULL DEFAULT '0' COMMENT '推荐',
  `is_hot` tinyint(4) NOT NULL DEFAULT '0' COMMENT '热门',
  `top` tinyint(1) NOT NULL DEFAULT '0',
  `istop` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态 1发布 0草稿',
  `views` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '浏览量',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
  `static_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '静态页路径',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布时间',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '建立时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `author_uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发稿人管理员ID',
  `audit_uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核人管理员ID',
  `audit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `audit_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '审核状态 0正常 1待审核 2已驳回',
  `submit_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '提交审核次数',
  `reject_reason` text COLLATE utf8mb4_unicode_ci COMMENT '最新驳回原因',
  PRIMARY KEY (`id`),
  KEY `idx_catid` (`catid`),
  KEY `idx_status_sort` (`status`,`sort`,`is_top`),
  KEY `idx_publish` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_content`
--

LOCK TABLES `yy_en_content` WRITE;
/*!40000 ALTER TABLE `yy_en_content` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_en_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_en_content_audit_log`
--

DROP TABLE IF EXISTS `yy_en_content_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_en_content_audit_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '内容ID',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1=提交(含修改说明) 2=驳回(含原因) 3=审核通过',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作人管理员ID',
  `note` text COLLATE utf8mb4_unicode_ci COMMENT '修改说明/驳回原因',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容审核日志-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_content_audit_log`
--

LOCK TABLES `yy_en_content_audit_log` WRITE;
/*!40000 ALTER TABLE `yy_en_content_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_en_content_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_en_link`
--

DROP TABLE IF EXISTS `yy_en_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_en_link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '站名',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '链接',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'LOGO',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '描述',
  `sort` int(11) NOT NULL DEFAULT '100',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友情链接-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_link`
--

LOCK TABLES `yy_en_link` WRITE;
/*!40000 ALTER TABLE `yy_en_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_en_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_en_message`
--

DROP TABLE IF EXISTS `yy_en_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_en_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `board_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '称呼',
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '电话',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '主题',
  `content` text COLLATE utf8mb4_unicode_ci COMMENT '留言内容',
  `reply` text COLLATE utf8mb4_unicode_ci COMMENT '后台回复',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'IP',
  `is_read` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否已读',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0待审核 1已通过 2已拒绝',
  `is_public` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否公开 0否 1是',
  `query_code` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '查询码',
  `reply_time` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `extend` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_ctime` (`create_time`),
  KEY `idx_query_code` (`query_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='留言-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_message`
--

LOCK TABLES `yy_en_message` WRITE;
/*!40000 ALTER TABLE `yy_en_message` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_en_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_en_message_board`
--

DROP TABLE IF EXISTS `yy_en_message_board`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_en_message_board` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL DEFAULT '',
  `title` varchar(80) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `fields` text,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `captcha` tinyint(1) NOT NULL DEFAULT '0',
  `show_public` tinyint(1) NOT NULL DEFAULT '0' COMMENT '前台是否显示是否公开选择 0否 1是',
  `default_public` tinyint(1) NOT NULL DEFAULT '1' COMMENT '默认公开 0否 1是',
  `sort` int(11) NOT NULL DEFAULT '100',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='留言板-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_message_board`
--

LOCK TABLES `yy_en_message_board` WRITE;
/*!40000 ALTER TABLE `yy_en_message_board` DISABLE KEYS */;
INSERT INTO `yy_en_message_board` VALUES (1,'default','Online Message','Leave a message, we will contact you soon','[{\"name\":\"name\",\"label\":\"Name\",\"type\":\"text\",\"required\":1,\"sort\":1},{\"name\":\"phone\",\"label\":\"Phone\",\"type\":\"text\",\"required\":0,\"sort\":2},{\"name\":\"email\",\"label\":\"Email\",\"type\":\"text\",\"required\":0,\"sort\":3},{\"name\":\"title\",\"label\":\"Subject\",\"type\":\"text\",\"required\":0,\"sort\":4},{\"name\":\"content\",\"label\":\"Content\",\"type\":\"textarea\",\"required\":1,\"sort\":5}]',1,0,0,1,100,1790557325,1790557325);
/*!40000 ALTER TABLE `yy_en_message_board` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_en_model`
--

DROP TABLE IF EXISTS `yy_en_model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_en_model` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型标识 article/product/...',
  `title` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型名称',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '模型说明',
  `list_template` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '默认列表模板',
  `show_template` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '默认内容页模板',
  `fields` text COLLATE utf8mb4_unicode_ci COMMENT '扩展字段定义(JSON)',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容模型-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_model`
--

LOCK TABLES `yy_en_model` WRITE;
/*!40000 ALTER TABLE `yy_en_model` DISABLE KEYS */;
INSERT INTO `yy_en_model` VALUES (1,'article','Article','General articles','list.html','show.html',NULL,1,1790238137,1790238137);
/*!40000 ALTER TABLE `yy_en_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_en_model_field`
--

DROP TABLE IF EXISTS `yy_en_model_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_en_model_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `model_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL DEFAULT '',
  `label` varchar(80) NOT NULL DEFAULT '',
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `options` text,
  `required` tinyint(4) NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL DEFAULT '100',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_model` (`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模型扩展字段-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_model_field`
--

LOCK TABLES `yy_en_model_field` WRITE;
/*!40000 ALTER TABLE `yy_en_model_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_en_model_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_en_setting`
--

DROP TABLE IF EXISTS `yy_en_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_en_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置名',
  `value` text COLLATE utf8mb4_unicode_ci COMMENT '配置值',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='语言设置-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_setting`
--

LOCK TABLES `yy_en_setting` WRITE;
/*!40000 ALTER TABLE `yy_en_setting` DISABLE KEYS */;
INSERT INTO `yy_en_setting` VALUES (15,'site.name','Yiyun CMS',0,0),(16,'site.url','http://localhost',0,0),(17,'site.logo','',0,0),(18,'site.icp','',0,0),(19,'site.keywords','Yiyun CMS,Content Management System',0,0),(20,'site.description','Yiyun CMS is a modern enterprise website CMS based on ThinkPHP 6.',0,0),(21,'site.copyright','Copyright © 2026 Yiyun CMS All Rights Reserved.',0,0),(22,'site.close','0',0,0),(23,'site.close_tip','Site under maintenance, please visit later.',0,0),(24,'seo.title','Yiyun CMS - Build Website Easier',0,0),(25,'seo.keywords','Yiyun CMS,Enterprise Website',0,0),(26,'seo.description','Yiyun CMS Enterprise Website CMS',0,0),(27,'message.audit','1',0,0),(28,'message.notify','0',0,0);
/*!40000 ALTER TABLE `yy_en_setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_lang`
--

DROP TABLE IF EXISTS `yy_lang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_lang` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '语言代码 cn/en/ru...（URL前缀+数据表前缀）',
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '语言名称 中文/English',
  `tpl_dir` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '前台模板文件夹名 template/{tpl_dir}',
  `flag` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '国旗/图标标识',
  `is_default` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否默认语言',
  `is_open` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序(小在前，第一个为默认)',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='语言';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_lang`
--

LOCK TABLES `yy_lang` WRITE;
/*!40000 ALTER TABLE `yy_lang` DISABLE KEYS */;
INSERT INTO `yy_lang` VALUES (1,'cn','中文','cn','CN',1,1,1,1790238137,1790238137),(2,'en','English','en','US',0,1,2,1790238137,1790238137);
/*!40000 ALTER TABLE `yy_lang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_member`
--

DROP TABLE IF EXISTS `yy_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '账号',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会员';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_member`
--

LOCK TABLES `yy_member` WRITE;
/*!40000 ALTER TABLE `yy_member` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `yy_visit_log`
--

DROP TABLE IF EXISTS `yy_visit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `yy_visit_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `page_url` varchar(255) NOT NULL DEFAULT '',
  `referer` varchar(255) NOT NULL DEFAULT '',
  `keyword` varchar(255) NOT NULL DEFAULT '',
  `engine` varchar(30) NOT NULL DEFAULT '',
  `area` varchar(100) NOT NULL DEFAULT '',
  `browser` varchar(30) NOT NULL DEFAULT '',
  `device` varchar(10) NOT NULL DEFAULT '',
  `os` varchar(30) NOT NULL DEFAULT '',
  `ip` varchar(46) NOT NULL DEFAULT '',
  `lang` varchar(10) NOT NULL DEFAULT '',
  `create_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ip_url_time` (`ip`,`page_url`,`create_time`),
  KEY `create_time` (`create_time`),
  KEY `area` (`area`),
  KEY `engine` (`engine`),
  KEY `browser` (`browser`),
  KEY `device` (`device`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='前台访问统计';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_visit_log`
--

LOCK TABLES `yy_visit_log` WRITE;
/*!40000 ALTER TABLE `yy_visit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `yy_visit_log` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-09 16:27:00


-- 栏目模板对齐前台模板（E 盘模板命名：list_lianxi 联系页 / guestbook_index 留言页）
UPDATE yy_cn_category SET template='list_lianxi' WHERE id=4;
UPDATE yy_cn_category SET template='guestbook_index' WHERE id=5;
UPDATE yy_en_category SET template='list_lianxi' WHERE id=4;
UPDATE yy_en_category SET template='guestbook_index' WHERE id=5;

-- 引导页开关（默认关闭）
INSERT INTO `yy_config` (`name`,`value`,`create_time`,`update_time`) VALUES ('base.splash','0',UNIX_TIMESTAMP(),UNIX_TIMESTAMP());

-- 系统功能总开关（v3.16，默认全部开启）
INSERT INTO `yy_config` (`name`,`value`,`create_time`,`update_time`) VALUES
('member.open','1',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('shop.open','1',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('service.open','1',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('comment.open','1',UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
('favorite.open','1',UNIX_TIMESTAMP(),UNIX_TIMESTAMP());
