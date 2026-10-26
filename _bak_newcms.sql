-- MySQL dump 10.13  Distrib 5.7.26, for Win64 (x86_64)
--
-- Host: localhost    Database: newcms
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
INSERT INTO `yy_admin` VALUES (1,'admin','$2y$10$4GS6O8EYrQ0StZDshv8Y5uwCMIUti5MEtDJhmQG.6D1H3iH83q8PO','超级管理员','','super',1,1791533457,'127.0.0.1',33,1790238137,1791533457),(2,'seqill','$2y$10$/R12a5hHhWnJIQo8XydKf.mXZL6j6s1fU7RVG50FgHY.soWFdVTqO','产品发布','','tougao',1,1791432664,'127.0.0.1',3,1791420117,1791432664);
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
) ENGINE=InnoDB AUTO_INCREMENT=360 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台日志';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_admin_log`
--

LOCK TABLES `yy_admin_log` WRITE;
/*!40000 ALTER TABLE `yy_admin_log` DISABLE KEYS */;
INSERT INTO `yy_admin_log` VALUES (1,1,'admin','login','登录后台成功','127.0.0.1',1790241511),(2,1,'admin','login','登录后台成功','127.0.0.1',1790556328),(3,1,'admin','login','登录后台成功','127.0.0.1',1790558025),(4,1,'admin','login','登录后台成功','127.0.0.1',1790558063),(5,1,'admin','model_add','新增模型：测试模型','127.0.0.1',1790558348),(6,1,'admin','board_add','新增留言板：测试留言板','127.0.0.1',1790558348),(7,1,'admin','field_add','新增字段：价格','127.0.0.1',1790558359),(8,1,'admin','field_add','新增字段：颜色','127.0.0.1',1790558359),(9,1,'admin','board_field','新增留言板字段：QQ','127.0.0.1',1790558359),(10,1,'admin','content_add','发布内容：冒烟测试内容','127.0.0.1',1790558437),(11,1,'admin','language_add','新增语言：俄语(ru) 模板文件夹 ru','127.0.0.1',1790561681),(12,1,'admin','block_add','自定义块：测试块','127.0.0.1',1790564005),(13,1,'admin','category_batchadd','批量添加栏目 2 个','127.0.0.1',1790564144),(14,1,'admin','ad_add','轮播图：测试轮播','127.0.0.1',1790566552),(15,1,'admin','content_batch','批量设置：recommend','127.0.0.1',1790566746),(16,1,'admin','category_edit','编辑栏目：新闻资讯','127.0.0.1',1790567097),(17,1,'admin','login','登录后台成功','127.0.0.1',1790573151),(18,1,'admin','login','登录后台成功','127.0.0.1',1790573163),(19,1,'admin','category_field','栏目#2 修改 sort=88','127.0.0.1',1790573873),(20,1,'admin','category_field','栏目#2 修改 name=新闻资讯','127.0.0.1',1790573873),(21,1,'admin','category_field','栏目#2 修改 is_nav=0','127.0.0.1',1790573873),(22,1,'admin','login','登录后台成功','127.0.0.1',1790575558),(23,1,'admin','category_field','栏目#4 修改 sort=66','127.0.0.1',1790576266),(24,1,'admin','category_field','栏目#4 修改 sort=66','127.0.0.1',1790576898),(25,1,'admin','category_field','栏目#4 修改 is_nav=1','127.0.0.1',1790576901),(26,1,'admin','category_field','栏目#4 修改 is_show=0','127.0.0.1',1790576902),(27,1,'admin','category_field','栏目#4 修改 is_nav=0','127.0.0.1',1790576902),(28,1,'admin','category_field','栏目#26 修改 sort=100','127.0.0.1',1790576922),(29,1,'admin','category_field','栏目#26 修改 sort=100','127.0.0.1',1790576923),(30,1,'admin','category_field','栏目#26 修改 ename=map2','127.0.0.1',1790576931),(31,1,'admin','category_field','栏目#26 修改 ename=map2','127.0.0.1',1790576932),(32,1,'admin','category_field','栏目#26 修改 name=城市分站1','127.0.0.1',1790576935),(33,1,'admin','category_field','栏目#26 修改 name=城市分站1','127.0.0.1',1790576936),(34,1,'admin','category_field','栏目#26 修改 is_nav=1','127.0.0.1',1790576966),(35,1,'admin','category_field','栏目#26 修改 is_nav=0','127.0.0.1',1790576966),(36,1,'admin','category_field','栏目#1 修改 sort=66','127.0.0.1',1790577006),(37,1,'admin','upload','上传文件：/upload/20260928/143932_6aba0ba4c15a6.png','127.0.0.1',1790577572),(38,1,'admin','ueditor_upload','UEditor上传：/upload/20260928/143932_6aba0ba4ce3b4.png','127.0.0.1',1790577572),(39,1,'admin','ueditor_upload','UEditor上传：/upload/20260928/144001_6aba0bc1a8fa1.png','127.0.0.1',1790577601),(40,1,'admin','upload','上传文件：/upload/20260928/144102_6aba0bfe835e1.png','127.0.0.1',1790577662),(41,1,'admin','ueditor_upload','UEditor上传：/upload/20260928/144102_6aba0bfe92e45.png','127.0.0.1',1790577662),(42,1,'admin','ueditor_upload','UEditor上传：/upload/20260928/144102_6aba0bfed6aec.png','127.0.0.1',1790577662),(43,1,'admin','ueditor_upload','UEditor上传：/upload/20260928/144211_6aba0c4343571.png','127.0.0.1',1790577731),(44,1,'admin','ueditor_upload','UEditor上传：/upload/20260928/144235_6aba0c5bf151d.png','127.0.0.1',1790577755),(45,1,'admin','upload','上传文件：/upload/20260928/144236_6aba0c5c45c6a.png','127.0.0.1',1790577756),(46,1,'admin','ueditor_upload','UEditor上传：/upload/20260928/144236_6aba0c5c53b63.png','127.0.0.1',1790577756),(47,1,'admin','upload','上传文件：/upload/20260928/144349_6aba0ca585047.jpg','127.0.0.1',1790577829),(48,1,'admin','upload','上传文件：/upload/20260928/144355_6aba0cab8309c.jpg','127.0.0.1',1790577835),(49,1,'admin','upload','上传文件：/upload/20260928/144412_6aba0cbca777a.png','127.0.0.1',1790577852),(50,1,'admin','category_field','栏目#4 修改 is_nav=1','127.0.0.1',1790578031),(51,1,'admin','category_field','栏目#4 修改 is_nav=0','127.0.0.1',1790578031),(52,1,'admin','content_batch','批量设置：hot','127.0.0.1',1790578046),(53,1,'admin','content_batch','批量设置：recommend','127.0.0.1',1790578049),(54,1,'admin','ad_add','轮播图：测试','127.0.0.1',1790578051),(55,1,'admin','content_batch','批量设置：top','127.0.0.1',1790578052),(56,1,'admin','content_batch','批量设置：recommend','127.0.0.1',1790578054),(57,1,'admin','content_batch','批量设置：recommend','127.0.0.1',1790578057),(58,1,'admin','content_batch','批量设置：hot','127.0.0.1',1790578059),(59,1,'admin','content_batch','批量设置：hot','127.0.0.1',1790578061),(60,1,'admin','content_batch','批量设置：recommend','127.0.0.1',1790578063),(61,1,'admin','content_batch','批量设置：top','127.0.0.1',1790578064),(62,1,'admin','content_batch','批量设置：status','127.0.0.1',1790578069),(63,1,'admin','content_batch','批量设置：status','127.0.0.1',1790578074),(64,1,'admin','content_batchcopy','批量复制内容 1 条','127.0.0.1',1790578097),(65,1,'admin','content_delete','删除内容：1 条','127.0.0.1',1790578131),(66,1,'admin','upload','上传文件：/upload/20260928/144912_6aba0de884571.png','127.0.0.1',1790578152),(67,1,'admin','upload','上传文件：/upload/20260928/144932_6aba0dfc7d4ce.png','127.0.0.1',1790578172),(68,1,'admin','ad_add','轮播图：多part测试','127.0.0.1',1790578238),(69,1,'admin','upload','上传文件：/upload/20260928/145119_6aba0e677e356.png','127.0.0.1',1790578279),(70,1,'admin','upload','上传文件：/upload/20260928/145216_6aba0ea07b9e3.png','127.0.0.1',1790578336),(71,1,'admin','upload','上传文件：/upload/20260928/145234_6aba0eb283b8a.png','127.0.0.1',1790578354),(72,1,'admin','upload','上传文件：/upload/20260928/145411_6aba0f1379fab.png','127.0.0.1',1790578451),(73,1,'admin','ad_add','轮播图：home_banner','127.0.0.1',1790578454),(74,1,'admin','ad_delete','删除广告位：','127.0.0.1',1790578586),(75,1,'admin','ad_delete','删除广告位：','127.0.0.1',1790578588),(76,1,'admin','ad_delete','删除广告位：','127.0.0.1',1790578591),(77,1,'admin','ad_delete','删除广告位：','127.0.0.1',1790578593),(78,1,'admin','ad_add','轮播图：123','127.0.0.1',1790578605),(79,1,'admin','ad_add','轮播图：456','127.0.0.1',1790578614),(80,1,'admin','content_batchcopy','批量复制内容 1 条','127.0.0.1',1790579483),(81,1,'admin','content_field','内容#19 修改 title','127.0.0.1',1790579707),(82,1,'admin','content_field','内容#19 修改 views','127.0.0.1',1790579730),(83,1,'admin','content_field','内容#19 修改 publish_time','127.0.0.1',1790579732),(84,1,'admin','content_batchcopy','批量复制内容 1 条','127.0.0.1',1790579804),(85,1,'admin','content_batch','批量设置：top','127.0.0.1',1790579830),(86,1,'admin','content_batch','批量设置：recommend','127.0.0.1',1790579899),(87,1,'admin','content_batch','批量设置：hot','127.0.0.1',1790579903),(88,1,'admin','content_batch','批量设置：top','127.0.0.1',1790579905),(89,1,'admin','content_batch','批量设置：recommend','127.0.0.1',1790579908),(90,1,'admin','content_field','内容#37 修改 title','127.0.0.1',1790579923),(91,1,'admin','content_field','内容#34 修改 views','127.0.0.1',1790579934),(92,1,'admin','content_batchcopy','批量复制内容 1 条','127.0.0.1',1790579974),(93,1,'admin','content_delete','删除内容：1 条','127.0.0.1',1790579980),(94,1,'admin','content_batchcopy','批量复制内容 1 条','127.0.0.1',1790579984),(95,1,'admin','content_delete','删除内容：1 条','127.0.0.1',1790579988),(96,1,'admin','content_batch','批量设置：status','127.0.0.1',1790579997),(97,1,'admin','content_batch','批量设置：status','127.0.0.1',1790580000),(98,1,'admin','content_field','内容#19 修改 status','127.0.0.1',1790580382),(99,1,'admin','content_field','内容#37 修改 status','127.0.0.1',1790582044),(100,1,'admin','content_field','内容#37 修改 status','127.0.0.1',1790582045),(101,1,'admin','content_batch','批量设置：top','127.0.0.1',1790582291),(102,1,'admin','content_batch','批量设置：recommend','127.0.0.1',1790582331),(103,1,'admin','content_batch','批量设置：hot','127.0.0.1',1790582369),(104,1,'admin','content_batch','批量设置：recommend','127.0.0.1',1790582716),(105,1,'admin','content_batch','批量设置：recommend','127.0.0.1',1790582719),(106,1,'admin','content_batch','批量设置：recommend','127.0.0.1',1790582722),(107,1,'admin','content_batch','批量设置：hot','127.0.0.1',1790582724),(108,1,'admin','content_batch','批量设置：recommend','127.0.0.1',1790582726),(109,1,'admin','content_batch','批量设置：top','127.0.0.1',1790582727),(110,1,'admin','content_field','内容#34 修改 status','127.0.0.1',1790582729),(111,1,'admin','content_field','内容#34 修改 status','127.0.0.1',1790582730),(112,1,'admin','content_field','内容#19 修改 publish_time','127.0.0.1',1790582911),(113,1,'admin','category_edit','编辑栏目：行业动态','127.0.0.1',1790584804),(114,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790585501),(115,1,'admin','upload','上传文件：/upload/20260928/165156_6aba2aaca2c97.jpg','127.0.0.1',1790585516),(116,1,'admin','upload','上传文件：/upload/20260928/165201_6aba2ab1cf4f4.jpg','127.0.0.1',1790585521),(117,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790585526),(118,1,'admin','ueditor_upload','UEditor上传：/upload/20260928/165220_6aba2ac4cf590.png','127.0.0.1',1790585540),(119,1,'admin','login','登录后台成功','127.0.0.1',1790667501),(120,1,'admin','login','登录后台成功','127.0.0.1',1790667592),(121,1,'admin','category_batchadd','批量添加栏目 1 个','127.0.0.1',1790670083),(122,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790670740),(123,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790670848),(124,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790671111),(125,1,'admin','upload','上传文件：/upload/20260929/163854_6abb791ec9103.jpg','127.0.0.1',1790671134),(126,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790671209),(127,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790671328),(128,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790671477),(129,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790671553),(130,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790671610),(131,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790671669),(132,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790671730),(133,1,'admin','login','登录后台成功','127.0.0.1',1790671816),(134,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790671839),(135,1,'admin','upload','上传文件：https://pic02.sq.seqill.cn/uploads/20260929/165050_6abb7beaf3b57.jpg','127.0.0.1',1790671851),(136,1,'admin','upload','上传文件：https://pic02.sq.seqill.cn/uploads/20260929/170829_6abb800d11eb9.jpg','127.0.0.1',1790672909),(137,1,'admin','board_edit','编辑留言板：在线留言','127.0.0.1',1790673812),(138,1,'admin','login','登录后台成功','127.0.0.1',1790739724),(139,1,'admin','login','登录后台成功','127.0.0.1',1790739742),(140,1,'admin','category_batchadd','批量添加栏目 2 个','127.0.0.1',1790739787),(141,1,'admin','login','登录后台成功','127.0.0.1',1790741843),(142,1,'admin','block_add','自定义块：测试图片块','127.0.0.1',1790742565),(143,1,'admin','block_edit','自定义块：ewm','127.0.0.1',1790742803),(144,1,'admin','login','登录后台成功','127.0.0.1',1790745639),(145,1,'admin','login','登录后台成功','127.0.0.1',1790753514),(146,1,'admin','static_mode','静态模式：开启','127.0.0.1',1790753520),(147,1,'admin','static_all','一键生成全站静态页[cn]','127.0.0.1',1790753543),(148,1,'admin','login','登录后台成功','127.0.0.1',1790753942),(149,1,'admin','login','登录后台成功','127.0.0.1',1790756403),(150,1,'admin','static_all','一键生成全站静态页[cn]','127.0.0.1',1790756428),(151,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790756488),(152,1,'admin','static_all','一键生成全站静态页[cn]','127.0.0.1',1790756492),(153,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1790756511),(154,1,'admin','content_batchcopylang','批量跨语言复制内容[en]：1 条','127.0.0.1',1790758365),(155,1,'admin','category_batchcopylang','批量跨语言复制栏目[en]：1 个栏目（含内容）','127.0.0.1',1790758606),(156,1,'admin','category_batchcopylang','批量跨语言复制栏目[en]：7 个栏目（含内容）','127.0.0.1',1790758621),(157,1,'admin','login','登录后台成功','127.0.0.1',1790759015),(158,1,'admin','category_batchcopylang','批量跨语言复制栏目[en]：8 个栏目（含内容）','127.0.0.1',1790759863),(159,1,'admin','category_batchcopylang','批量跨语言复制栏目[en]：3 个栏目（含内容）','127.0.0.1',1790759887),(160,1,'admin','admin_role_add','新增角色：测试角色','127.0.0.1',1790760420),(161,1,'admin','login','登录后台成功','127.0.0.1',1791419888),(162,1,'admin','login','登录后台成功','127.0.0.1',1791419963),(163,1,'admin','admin_add','新增管理员：seqill','127.0.0.1',1791420117),(164,2,'seqill','login','登录后台成功','127.0.0.1',1791420143),(165,1,'admin','admin_role_edit','编辑角色：投稿员','127.0.0.1',1791420181),(166,1,'admin','admin_role_edit','编辑角色：投稿员','127.0.0.1',1791420200),(167,2,'seqill','upload','上传文件：https://pic02.sq.seqill.cn/uploads/20261008/084425_6ac6e7698d31d.jpg','127.0.0.1',1791420265),(168,2,'seqill','upload','上传文件：https://pic02.sq.seqill.cn/uploads/20261008/084510_6ac6e7960956c.jpg','127.0.0.1',1791420310),(169,2,'seqill','upload','上传文件：https://pic02.sq.seqill.cn/uploads/20261008/084513_6ac6e799d2819.jpg','127.0.0.1',1791420321),(170,2,'seqill','upload','上传文件：https://pic02.sq.seqill.cn/uploads/20261008/084520_6ac6e7a00f18c.jpg','127.0.0.1',1791420323),(171,2,'seqill','content_add','发布内容：投稿员测试','127.0.0.1',1791420356),(172,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1791421063),(173,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1791421142),(174,1,'admin','ueditor_upload','UEditor上传：/upload/20261008/090637_6ac6ec9dbcf04.doc','127.0.0.1',1791421597),(175,1,'admin','ueditor_upload','UEditor上传：/upload/20261008/091144_6ac6edd09c172.doc','127.0.0.1',1791421904),(176,1,'admin','ueditor_upload','UEditor上传：/upload/20261008/091411_6ac6ee638f042.doc','127.0.0.1',1791422051),(177,1,'admin','ueditor_upload','UEditor上传：/upload/20261008/093246_6ac6f2be3e20c.doc','127.0.0.1',1791423166),(178,1,'admin','ueditor_upload','UEditor上传：/upload/20261008/093358_6ac6f306e667d.doc','127.0.0.1',1791423238),(179,1,'admin','content_add','发布内容：213','127.0.0.1',1791423265),(180,1,'admin','ueditor_upload','UEditor上传：/upload/20261008/093457_6ac6f34120493.doc','127.0.0.1',1791423297),(181,1,'admin','content_edit','编辑内容：213','127.0.0.1',1791423299),(182,1,'admin','upload','上传文件：/upload/20261008/093720_6ac6f3d021216.jpg','127.0.0.1',1791423440),(183,1,'admin','upload','上传文件：/upload/20261008/093729_6ac6f3d951645.jpg','127.0.0.1',1791423449),(184,1,'admin','upload','上传文件：/upload/20261008/093736_6ac6f3e037010.jpg','127.0.0.1',1791423456),(185,1,'admin','content_edit','编辑内容：213','127.0.0.1',1791423460),(186,1,'admin','ueditor_upload','UEditor上传：/upload/20261008/093816_6ac6f408b582e.jpg','127.0.0.1',1791423496),(187,1,'admin','ueditor_upload','UEditor上传：/upload/20261008/093816_6ac6f408b582d.jpg','127.0.0.1',1791423496),(188,1,'admin','ueditor_upload','UEditor上传：/upload/20261008/093816_6ac6f408bbdf3.jpg','127.0.0.1',1791423496),(189,1,'admin','ueditor_upload','UEditor上传：/upload/20261008/093816_6ac6f408c11bd.jpg','127.0.0.1',1791423496),(190,1,'admin','login','登录后台成功','127.0.0.1',1791424135),(191,1,'admin','upload','上传文件：/upload/20261008/095031_6ac6f6e74365c.jpg','127.0.0.1',1791424231),(192,1,'admin','upload','上传文件：/upload/20261008/095031_6ac6f6e743cf1.jpg','127.0.0.1',1791424231),(193,1,'admin','upload','上传文件：/upload/20261008/095031_6ac6f6e743fd1.jpg','127.0.0.1',1791424231),(194,1,'admin','upload','上传文件：/upload/20261008/095031_6ac6f6e746e6d.jpg','127.0.0.1',1791424231),(195,1,'admin','upload','上传文件：/upload/20261008/095031_6ac6f6e74d73f.jpg','127.0.0.1',1791424231),(196,1,'admin','upload','上传文件：/upload/20261008/095031_6ac6f6e74dbe0.jpg','127.0.0.1',1791424231),(197,1,'admin','upload','上传文件：/upload/20261008/095031_6ac6f6e74df4d.jpg','127.0.0.1',1791424231),(198,1,'admin','upload','上传文件：/upload/20261008/095042_6ac6f6f20c736.jpg','127.0.0.1',1791424242),(199,1,'admin','content_batchcopy','批量复制内容 1 条','127.0.0.1',1791424413),(200,1,'admin','content_batchcopy','批量复制内容 2 条','127.0.0.1',1791424426),(201,1,'admin','login','登录后台成功','127.0.0.1',1791424517),(202,1,'admin','admin_role_edit','编辑角色：投稿员','127.0.0.1',1791428156),(203,2,'seqill','content_add','发布内容：投稿审核测试-自动草稿','127.0.0.1',1791428433),(204,1,'admin','content_edit','编辑内容：投稿审核测试-自动草稿','127.0.0.1',1791428458),(205,1,'admin','content_add','发布内容：管理员直接发布-免审核测试','127.0.0.1',1791428480),(206,1,'admin','login','登录后台成功','127.0.0.1',1791428700),(207,1,'admin','content_add','发布内容：456','127.0.0.1',1791428717),(208,0,'','login_fail','账号或密码错误：seqill','127.0.0.1',1791428764),(209,0,'','login_fail','账号或密码错误：seqill','127.0.0.1',1791428772),(210,0,'','login_fail','账号或密码错误：seqill','127.0.0.1',1791428780),(211,1,'admin','admin_edit','编辑管理员：seqill','127.0.0.1',1791428788),(212,2,'seqill','login','登录后台成功','127.0.0.1',1791428811),(213,2,'seqill','upload','上传文件：/upload/20261008/110715_6ac708e385b8b.jpg','127.0.0.1',1791428835),(214,2,'seqill','content_add','发布内容：0000','127.0.0.1',1791428840),(215,1,'admin','content_add','发布内容：人员显示验证-管理员发布','127.0.0.1',1791429139),(216,1,'admin','content_field','内容#46 修改 status','127.0.0.1',1791429363),(217,1,'admin','content_field','内容#46 修改 status','127.0.0.1',1791429370),(218,2,'seqill','content_add','发布内容：审核闭环实测-投稿A','127.0.0.1',1791430352),(219,1,'admin','content_audit','驳回内容#48：配图缺失，请补充产品实拍图后重新提交','127.0.0.1',1791430537),(220,2,'seqill','content_edit','编辑内容：审核闭环实测-投稿A','127.0.0.1',1791430566),(221,1,'admin','content_audit','审核通过内容#48','127.0.0.1',1791430606),(222,1,'admin','content_field','内容#46 修改 status','127.0.0.1',1791430791),(223,1,'admin','content_field','内容#46 修改 status','127.0.0.1',1791430804),(224,1,'admin','content_field','内容#46 修改 status','127.0.0.1',1791430806),(225,1,'admin','content_field','内容#46 修改 status','127.0.0.1',1791430808),(226,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791430905),(227,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791430906),(228,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791430907),(229,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791430908),(230,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791430909),(231,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791430910),(232,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791430918),(233,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791430919),(234,1,'admin','content_batch','批量设置：top','127.0.0.1',1791431330),(235,1,'admin','content_batch','批量设置：top','127.0.0.1',1791431338),(236,1,'admin','content_audit','驳回内容#49：示例驳回原因：正文缺少图片','127.0.0.1',1791431385),(237,1,'admin','content_audit','审核通过内容#49','127.0.0.1',1791431412),(238,1,'admin','content_edit','编辑内容：审核操作区实测','127.0.0.1',1791431436),(239,1,'admin','content_edit','编辑内容：0000','127.0.0.1',1791432008),(240,1,'admin','content_edit','编辑内容：0000','127.0.0.1',1791432032),(241,1,'admin','content_edit','编辑内容：0000','127.0.0.1',1791432038),(242,1,'admin','content_field','内容#46 修改 status','127.0.0.1',1791432071),(243,1,'admin','content_field','内容#46 修改 status','127.0.0.1',1791432072),(244,1,'admin','content_audit','驳回内容#50：实测驳回：内容需修正后重新提交','127.0.0.1',1791432347),(245,1,'admin','content_audit','驳回内容#46：图片尺寸不对','127.0.0.1',1791432639),(246,2,'seqill','login','登录后台成功','127.0.0.1',1791432664),(247,2,'seqill','content_edit','编辑内容：0000','127.0.0.1',1791432702),(248,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1791433327),(249,2,'seqill','content_add','发布内容：审核关闭直接上架实测','127.0.0.1',1791433688),(250,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1791433706),(251,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1791433866),(252,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1791433901),(253,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791433919),(254,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791433923),(255,1,'admin','login','登录后台成功','127.0.0.1',1791437219),(256,1,'admin','visit_view','查看浏览统计（all）','127.0.0.1',1791437289),(257,1,'admin','visit_view','查看浏览统计（all）','127.0.0.1',1791437289),(258,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791437297),(259,1,'admin','visit_view','查看浏览统计（week）','127.0.0.1',1791437297),(260,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791437364),(261,1,'admin','menu_toggle','已隐藏菜单：admin/ad/index','127.0.0.1',1791437695),(262,1,'admin','menu_toggle','已显示菜单：admin/ad/index','127.0.0.1',1791437744),(263,1,'admin','menu_toggle','已隐藏菜单：admin/ad/index','127.0.0.1',1791437877),(264,1,'admin','menu_toggle','已显示菜单：admin/ad/index','127.0.0.1',1791437879),(265,1,'admin','menu_toggle','已隐藏菜单：admin/link/index','127.0.0.1',1791437968),(266,1,'admin','menu_toggle','已显示菜单：admin/link/index','127.0.0.1',1791437972),(267,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791438302),(268,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791439399),(269,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791439634),(270,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1791441898),(271,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791442336),(272,1,'admin','category_field','栏目#4 修改 is_show=1','127.0.0.1',1791442524),(273,1,'admin','category_field','栏目#4 修改 is_show=0','127.0.0.1',1791442525),(274,1,'admin','category_field','栏目#4 修改 is_show=1','127.0.0.1',1791442525),(275,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791442543),(276,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791442543),(277,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791442544),(278,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791442545),(279,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791442546),(280,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443185),(281,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443187),(282,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443200),(283,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443208),(284,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443216),(285,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443218),(286,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443222),(287,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443223),(288,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443224),(289,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443233),(290,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443234),(291,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443242),(292,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443244),(293,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443247),(294,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443248),(295,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443256),(296,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443265),(297,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443266),(298,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791443272),(299,1,'admin','login','登录后台成功','127.0.0.1',1791443337),(300,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791444404),(301,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791444447),(302,1,'admin','content_field','内容#45 修改 status','127.0.0.1',1791444451),(303,1,'admin','category_field','栏目#3 修改 is_nav=0','127.0.0.1',1791445041),(304,1,'admin','category_field','栏目#3 修改 is_nav=1','127.0.0.1',1791445041),(305,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791445073),(306,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791445076),(307,1,'admin','message_reply','回复留言：1','127.0.0.1',1791445409),(308,1,'admin','message_reply','回复留言：公开测试用户','127.0.0.1',1791446635),(309,1,'admin','message_toggle','修改留言字段：is_public=0','127.0.0.1',1791446635),(310,1,'admin','message_toggle','修改留言字段：is_public=1','127.0.0.1',1791446636),(311,1,'admin','message_toggle','修改留言字段：is_public=1','127.0.0.1',1791446685),(312,1,'admin','message_toggle','修改留言字段：is_public=0','127.0.0.1',1791446855),(313,1,'admin','message_toggle','修改留言字段：is_public=1','127.0.0.1',1791446856),(314,1,'admin','template_edit','编辑模板文件：cn/show.html','127.0.0.1',1791447053),(315,1,'admin','template_edit','编辑模板文件：cn/show.html','127.0.0.1',1791447063),(316,1,'admin','template_edit','编辑模板文件：cn/show_news.html','127.0.0.1',1791447083),(317,1,'admin','template_edit','编辑模板文件：cn/show_news.html','127.0.0.1',1791447090),(318,1,'admin','template_edit','编辑模板文件：cn/show.html','127.0.0.1',1791447100),(319,1,'admin','template_edit','编辑模板文件：cn/show.html','127.0.0.1',1791447154),(320,1,'admin','template_edit','编辑模板文件：cn/show.html','127.0.0.1',1791447166),(321,1,'admin','static_all','一键生成全站静态页[cn]','127.0.0.1',1791447176),(322,1,'admin','message_reply','回复留言：7','127.0.0.1',1791447236),(323,1,'admin','message_toggle','修改留言字段：is_public=1','127.0.0.1',1791447246),(324,1,'admin','message_reply','回复留言：7','127.0.0.1',1791447355),(325,1,'admin','board_edit','编辑留言板：在线留言','127.0.0.1',1791448112),(326,1,'admin','board_edit','编辑留言板：在线留言','127.0.0.1',1791448113),(327,1,'admin','board_edit','编辑留言板：在线留言','127.0.0.1',1791448113),(328,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791448577),(329,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1791449938),(330,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1791449951),(331,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1791450038),(332,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1791450047),(333,1,'admin','cat_model_add','新增栏目模型：产品栏目模型','127.0.0.1',1791452427),(334,1,'admin','cat_field_add','新增栏目模型字段：副图片','127.0.0.1',1791452449),(335,1,'admin','category_edit','编辑栏目：人造草坪','127.0.0.1',1791452560),(336,1,'admin','login','登录后台成功','127.0.0.1',1791453030),(337,1,'admin','static_all','一键生成全站静态页（全部语言）','127.0.0.1',1791453509),(338,1,'admin','static_all','一键生成全站静态页（全部语言）','127.0.0.1',1791453626),(339,1,'admin','visit_view','查看浏览统计（today）','127.0.0.1',1791453659),(340,1,'admin','setting','更新网站设置[cn]','127.0.0.1',1791454125),(341,1,'admin','logout','退出后台','127.0.0.1',1791454224),(342,0,'','login_fail','账号或密码错误：admin','127.0.0.1',1791454889),(343,0,'','login_fail','账号或密码错误：admin','127.0.0.1',1791454909),(344,1,'admin','login','登录后台成功','127.0.0.1',1791454935),(345,1,'admin','category_field','栏目#1 修改 pagesize=5','127.0.0.1',1791454987),(346,1,'admin','category_field','栏目#9 修改 pagesize=5','127.0.0.1',1791455017),(347,1,'admin','upload','上传文件：/upload/20261008/194213_6ac781950872b.png','127.0.0.1',1791459733),(348,1,'admin','ueditor_upload','UEditor上传：/upload/20261008/194213_6ac781951645f.png','127.0.0.1',1791459733),(349,1,'admin','content_field','内容#1 修改 title','127.0.0.1',1791459733),(350,1,'admin','category_field','栏目#2 修改 sort=5','127.0.0.1',1791459733),(351,1,'admin','ueditor_upload','UEditor上传：/upload/20261008/194248_6ac781b820686.png','127.0.0.1',1791459768),(352,1,'admin','login','登录后台成功','127.0.0.1',1791506527),(353,1,'admin','static_all','一键生成全站静态页（全部语言）','127.0.0.1',1791506813),(354,1,'admin','login','登录后台成功','127.0.0.1',1791507038),(355,1,'admin','login','登录后台成功','127.0.0.1',1791510470),(356,1,'admin','logout','退出后台','127.0.0.1',1791517352),(357,1,'admin','login','登录后台成功','127.0.0.1',1791524813),(358,1,'admin','login','登录后台成功','127.0.0.1',1791525882),(359,1,'admin','login','登录后台成功','127.0.0.1',1791533457);
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
INSERT INTO `yy_admin_role` VALUES (1,'super','超级管理员','*',NULL,NULL,'拥有全部权限',1,1,0,0,0,0),(2,'admin','普通管理员','[]',NULL,NULL,'默认后台账号，可配置权限',1,1,1,0,0,0),(3,'tougao','投稿员','[\"content\",\"upload\",\"ueditor\"]','{\"cn\":[3]}','[]','仅可发布/管理内容',0,1,2,0,1791420200,1),(4,'message','留言查看','[\"message\",\"board\"]',NULL,NULL,'仅可查看留言',0,1,3,0,0,0),(5,'seo','SEO优化','[\"link\",\"setting\"]',NULL,NULL,'可管理友情链接等SEO相关',0,1,4,0,0,0);
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
INSERT INTO `yy_cat_model` VALUES (1,'default','默认栏目模型',1791452132,1791452132),(2,'product','产品栏目模型',1791452427,1791452427);
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
INSERT INTO `yy_cat_model_field` VALUES (1,2,'sub_image','副图片','image','',100,1791452449,1791452449);
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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告位-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_ad`
--

LOCK TABLES `yy_cn_ad` WRITE;
/*!40000 ALTER TABLE `yy_cn_ad` DISABLE KEYS */;
INSERT INTO `yy_cn_ad` VALUES (26,0,'yunu_1','pc1',0,0,'','/upload/image/20260720/bf1d8d9a15f0f19afc6195a7dd6e7114.jpg','#',0,1,1791461938,1791461938),(27,0,'yunu_1','pc2',0,0,'','/upload/image/20260720/bf1d8d9a15f0f19afc6195a7dd6e7114.jpg','#',0,1,1791461938,1791461938),(28,0,'yunu_1','pc3',0,0,'','/upload/image/20260720/bf1d8d9a15f0f19afc6195a7dd6e7114.jpg','#',0,1,1791461938,1791461938),(29,0,'yunu_2','wap1',0,0,'','/upload/image/20220106/1c715c24fae944f74f9d3dcfda8ba924.jpg','#',0,1,1791461938,1791461938),(30,0,'yunu_2','wap2',0,0,'','/upload/image/20220106/9f90b4b637b7c422be5de63204435e47.jpg','#',0,1,1791461938,1791461938),(31,0,'yunu_2','wap3',0,0,'','/upload/image/20220106/6c64daf094fb72a5b667bcd44b68a612.jpg','#',0,1,1791461938,1791461938);
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
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='栏目-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_category`
--

LOCK TABLES `yy_cn_category` WRITE;
/*!40000 ALTER TABLE `yy_cn_category` DISABLE KEYS */;
INSERT INTO `yy_cn_category` VALUES (59,0,1,0,'公司简介','company','about us','redirect',1,'@67',0,'','','','/upload/image/20260722/e6387987bf9cf110ca931fc1f5d3d9ee.jpg','',NULL,'list_page','','company',1,1,1,10,1791461938,1791511068),(60,0,1,0,'新闻资讯','news','news','list',1,'',0,'','','','/upload/image/20260722/3533cc4a334c6dfc26779adc90c95f40.jpg','',NULL,'list_article','show_article','news',1,1,3,10,1791461938,1791463262),(61,0,4,0,'产品中心','pro','product center','list',1,'',0,'','','','/upload/image/20260722/39998448fe8246aab0855e7983611d2a.jpg','',NULL,'list_product','show_product','pro',1,1,2,10,1791461938,1791463262),(62,0,1,0,'企业优势','solution','solution','redirect',1,'@77',0,'','','','','',NULL,'list_page','','solution',1,0,0,10,1791461938,1791463260),(63,0,3,0,'工程案例','case','case','list',0,'',0,'','','','/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg','',NULL,'list_pic','show_pic','case',1,1,4,10,1791461938,1791463262),(64,0,1,0,'联系我们','contact','contact us','redirect',1,'@84',0,'','','','/upload/image/20260722/64af24c590c9062b45a31dce88da9825.jpg','',NULL,'list_page','','contact',1,1,6,10,1791461938,1791511069),(65,0,1,0,'城市分站','map','','single',1,'',0,'','','','','',NULL,'list_map','','map.html',1,0,0,10,1791461938,1791463260),(66,0,1,0,'人才招聘','rencaizhaopin','','list',0,'',0,'','','','/upload/image/20260722/c969844d5b22f8179140bb41fdccdce7.jpg','',NULL,'list_article','show_article','rencaizhaopin',1,1,5,10,1791461938,1791463263),(67,59,1,0,'公司简介','aboutus','','single',1,'',0,'','','<p>建筑工程施工，给排水工程、市政园林绿化工程，体育场馆设施、设计、施工、技术咨询；翻修及维护工程、防水保温工程、弱电工程设计、施工；照明器材、设计施工、安装及维护；体育设备租赁、体育赛事活动策划...</p>','','<p>辰宏体育是一家经营体育用品、健身器材，承建体育场地工程的专业性公司。公司以“致力于体育场地建设，创造舒适的运动空间”为宗旨，本着“质量、服务为根本，诚信经营为基础，共赢互利为目标”的经营方针竭诚为社会及广大客户服务。</p><p>公司以“团结进取、开拓创新”的企业精神，并借鉴先进管理思想，逐步提高公司员工的业务水平和服务、质量意识，精心打造了一个业务精湛、服务完善的优秀团队和一支经验丰富、技术一流、设备先进的施工队伍。公司始终贯彻“质量是企业生存和发展的根本，持续改进产品及服务质量以满足广大客户的需求是公司发展的永恒主题”的质量方针，采用优质的原材料，严格执行国际、国家体育场地建造标准，为广大客户打造了一个又一个美丽舒适的运动空间。</p><p>经营范围：</p><p>承建：丙烯酸，硅PU体育场地；塑胶跑道；塑胶球场；人造草坪以及游泳池；休闲高尔夫；壁球场等体育场地；</p><p>销售：体育用品；健身器材；健身路径；塑胶跑道；人造草坪；丙烯酸球场涂料；PVC运动、商业、医疗地板；壁球馆批荡料；乳胶漆等铺设材料。</p><p>客户满意是我们工作的目标，也是衡量我们工作好坏的标准。辰宏人将一如既往地以优质的产品、良好的信誉、全面完善的服务寻求与广大客户的合作！</p>',NULL,'list_page','','aboutus.html',1,1,0,10,1791461938,1791511202),(68,59,1,0,'企业文化','culture','','single',1,'',0,'','','','','<p>资料准备中...</p>',NULL,'list_page','','culture.html',1,1,0,10,1791461938,1791511202),(69,60,1,0,'行业动态','industrynews','','list',0,'',0,'','','','','',NULL,'list_article','show_article','news/industrynews',1,1,0,10,1791461938,1791463308),(70,60,1,0,'公司新闻','companynews','','list',0,'',0,'','','','','',NULL,'list_article','show_article','news/companynews',1,1,0,10,1791461938,1791463308),(71,60,1,0,'常见问题','qa','','list',0,'',0,'','','','','',NULL,'list_article','show_article','news/qa',1,1,0,10,1791461938,1791463308),(72,61,4,0,'人造草坪','pro1','','list',0,'',0,'','','','','',NULL,'list_product','show_product','pro/pro1',1,1,0,10,1791461938,1791463309),(73,61,4,0,'塑料跑道','pro2','','list',0,'',0,'','','','','',NULL,'list_product','show_product','pro/pro2',1,1,0,10,1791461938,1791463309),(74,61,4,0,'运动地板','pro3','','list',0,'',0,'','','','','',NULL,'list_product','show_product','pro/pro3',1,1,0,10,1791461938,1791463309),(75,61,4,0,'体育设备','pro4','','list',0,'',0,'','','','','',NULL,'list_product','show_product','pro/pro4',1,1,0,10,1791461938,1791463309),(76,61,4,0,'体育用品','pro5','','list',0,'',0,'','','','','',NULL,'list_product','show_product','pro/pro5',1,1,0,10,1791461938,1791463309),(77,62,1,0,'拥有多位高素质、高水准的技术人才','solution1','沈阳辰宏体育设施有限公司','redirect',1,'@67',0,'','','<p>应用场景：1.25G~40G光模块 /TOSA/ROSA制造测试</p>','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','<p>方案描述</p><p>随着数据中心和5G商用网络的大规模建设，对于高速模块的需求量会越来越大。目前进入批量生产的高速光模块主要有：</p><p>● 400G速率：QSFP/QSFP-DD</p><p>● 200G速率：QSFP/QSFP-DD</p><p>● 100G速率：QSFP28/CFP4/CFP2/CFP/CXP</p><p>● 25G速率：SFP28</p><p>● 10G速率：SFP+/XFP</p><p>光模块测试面临的挑战</p><p>● 光模块测试项目多，测试复杂</p><p>● 整个测试占用大量的时间与人力</p><p>● 测量设备费用高昂，测试成本高</p>',NULL,'list_page','','solution1',1,0,7,10,1791461938,1791511206),(78,62,1,0,'企业简介','solution2','沈阳辰宏体育设施有限公司','redirect',1,'@67',0,'','','<p>应用场景：MPO生产制造过程中的端面清洁、3D检测、端面检测、极性检测、光学性能测试。</p>','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','',NULL,'list_page','','solution2',1,1,0,10,1791461938,1791511204),(79,62,1,0,'发展目标','solution3','沈阳辰宏体育设施有限公司','redirect',1,'@67',0,'','','<p>应用场景：无源器件、无源模块生产制造测试以及产品研发技术验证。</p>','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','',NULL,'list_page','','solution3',1,1,0,10,1791461938,1791511204),(80,62,1,0,'经营理念','solution4','沈阳辰宏体育设施有限公司','redirect',1,'@67',0,'','','<p>应用场景：实验室和制作测试</p>','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','',NULL,'list_page','','solution4',1,1,0,10,1791461938,1791511204),(81,62,1,0,'环境展示','solution5','沈阳辰宏体育设施有限公司','redirect',1,'@67',0,'','','<p>应用场景：CWDM无源器件、无源模块多工位自动扫描测试系统</p>','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','',NULL,'list_page','','solution5',1,1,0,10,1791461938,1791511204),(82,62,1,0,'专业塑造品质，服务创造未来','solution6','沈阳辰宏体育设施有限公司','redirect',1,'@67',0,'','','<p>应用场景：DWDM无源器件、无源模块多工位自动扫描测试系统</p>','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','',NULL,'list_page','','solution6',1,1,0,10,1791461938,1791511204),(83,62,1,0,'厂家直销，同等价格质量更好','solution7','沈阳辰宏体育设施有限公司','redirect',1,'@67',0,'','','<p>应用场景：无源器件、无源模块生产制造可靠性测试，新产品研发可靠性技术验证</p>','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','',NULL,'list_page','','solution7',1,1,0,10,1791461938,1791511204),(84,64,1,0,'联系我们','contactus','','single',1,'',0,'','','','','<p><strong>北京某某科技有限公司</strong></p><p>公司地址：北京市某某区某某大街某某大厦某某层</p><p>联系电话：010-99999999</p><p>公司邮箱：88888888@qq.com</p>',NULL,'list_contact','','contactus.html',1,1,0,10,1791461938,1791511204),(85,64,1,0,'在线留言','message','','single',1,'',0,'','','','','',NULL,'list_message','','message.html',1,1,0,10,1791461938,1791511204),(86,72,4,0,'温度传感器1','1','','list',0,'',0,'','','','','',NULL,'list_product','show_product','pro/pro1/1',1,0,0,10,1791461938,1791463309);
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
  `publish_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
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
  KEY `idx_publish` (`publish_time`)
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_content`
--

LOCK TABLES `yy_cn_content` WRITE;
/*!40000 ALTER TABLE `yy_cn_content` DISABLE KEYS */;
INSERT INTO `yy_cn_content` VALUES (80,69,1,'电子元器件行业如何适应时代需求','','','','','','/upload/image/20211225/82f6a51bca9d203de9882fbb97be4593.png','','','','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更</p>','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更加紧密，进而推动新一轮的产业创新浪潮。在三网融合建设中，无论是网络设备还是终端设备都离不开各种元器件。网络的改造升级、终端设备的多样化设计都要依托关键元器件技术的革新。建设高速铁路，需要现代化的路网指挥系统、现代化的高速机车，这些都和电子元器件尤其是大功率电力电子器件密不可分。随着新能源的广泛应用，对环保节能型电子元器件产品的需求也将越来越大。这都为相关的元器件企业提供了巨大的市场机遇。但是，目前我国的电子元器件产品，无论技术还是规模都不足以支撑起这些新兴产业的发展。国内的电子元器件从业者，特别是资金雄厚的大企业应该及早关注这一领域，尽早切入这些行业。</p><p>顶级专家莅临会场 明星厂商寻求共识</p><p>电子元器件及相关材料是国际高新技术领域最活跃的研究前沿和创新热点之一。为进一步推动我国电子元器件的研究和产业发展，加强政府、高校、科研机构与企业之间的沟通交流，促进新型电子元器件的技术进步与应用水平提高，进而满足我国电子信息行业飞速发展和《中国制造2025》的新要求，中国电子学会将于2017年10月26日在上海第90届中国电子展期间，召开中国电子元件与材料技术发展论坛。本届论坛将围绕“4G/5G时代的新型元器件发展与挑战”主题，针对行业前沿及应用技术，邀请国家重点研发计划“战略性电子信息材料”项目首席科学家孙蓉、中国电子元件行业协会秘书长温学礼等专家，以及广东风华高新科、深圳顺络电子、潮州三环、山东国瓷、深圳市宇阳科技、太阳诱电、京瓷、TDK等多家全球知名元件厂商和材料厂商代表进行精彩演讲。会议期间将就“国内外元件与材料发展现状与趋势”、“陶瓷介质滤波器在4G/5G通讯中的应用”、“LTCC器件在移动通信中的应用”、“手机陶瓷背板技术趋势分析”、“信息功能陶瓷原材料的国产化”、“超小型片式电容的技术发展”等热点话题进行专题研讨，寻求各界共识。</p><p>关注新兴领域 紧握发展良机</p><p>科技创新和新兴领域的崛起对任何行业的发展都至关重要。为应对国际金融危机的中长期影响，世界各国纷纷将科技创新作为战略举措，大力发展新能源、新材料、电子信息、节能环保等新兴产业，以抢占未来科技和产业发展的制高点。电子元器件行业要把握发展机遇，取得进一步发展，需重点关注以上新应用领域及政策导向，如三网融合、无线互联、物联网、新能源及高铁建设等领域。</p><p>以物联网产业为例，传感器和敏感元器件是未来万亿元产业的基础，必定会得到国家的大力扶持。未来几年，物联网将不断渗透到社会生活中，包括智能交通、智能城市、环境保护、公共安全、智能电网等众多领域，带动电子元器件等产业高速发展。据有关研究机构预测，到2020年，物联网相关的嵌入式芯片、传感器、无线射频的“智能物件”数或超过2万亿。近日无锡物联网产业研究院战略部的韩夏在谈到“中国元件制造业在物联网时代的机会”时透露，上海浦东机场一期工程的35公里围栏建设项目，大概需要3万多个物联网节点，而每个节点均由各式传感器及其他电子器件构成，元器件整体需求非常庞大。</p><p><br/></p>','',0,0,0,0,0,1,6,0,'news/industrynews/80.html',1640419044,1640419044,1791527800,0,0,0,0,0,''),(81,69,1,'电子元器件行业如何适应时代需求','','','','','','/upload/image/20211225/82f6a51bca9d203de9882fbb97be4593.png','','','','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更</p>','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更加紧密，进而推动新一轮的产业创新浪潮。在三网融合建设中，无论是网络设备还是终端设备都离不开各种元器件。网络的改造升级、终端设备的多样化设计都要依托关键元器件技术的革新。建设高速铁路，需要现代化的路网指挥系统、现代化的高速机车，这些都和电子元器件尤其是大功率电力电子器件密不可分。随着新能源的广泛应用，对环保节能型电子元器件产品的需求也将越来越大。这都为相关的元器件企业提供了巨大的市场机遇。但是，目前我国的电子元器件产品，无论技术还是规模都不足以支撑起这些新兴产业的发展。国内的电子元器件从业者，特别是资金雄厚的大企业应该及早关注这一领域，尽早切入这些行业。</p><p>顶级专家莅临会场 明星厂商寻求共识</p><p>电子元器件及相关材料是国际高新技术领域最活跃的研究前沿和创新热点之一。为进一步推动我国电子元器件的研究和产业发展，加强政府、高校、科研机构与企业之间的沟通交流，促进新型电子元器件的技术进步与应用水平提高，进而满足我国电子信息行业飞速发展和《中国制造2025》的新要求，中国电子学会将于2017年10月26日在上海第90届中国电子展期间，召开中国电子元件与材料技术发展论坛。本届论坛将围绕“4G/5G时代的新型元器件发展与挑战”主题，针对行业前沿及应用技术，邀请国家重点研发计划“战略性电子信息材料”项目首席科学家孙蓉、中国电子元件行业协会秘书长温学礼等专家，以及广东风华高新科、深圳顺络电子、潮州三环、山东国瓷、深圳市宇阳科技、太阳诱电、京瓷、TDK等多家全球知名元件厂商和材料厂商代表进行精彩演讲。会议期间将就“国内外元件与材料发展现状与趋势”、“陶瓷介质滤波器在4G/5G通讯中的应用”、“LTCC器件在移动通信中的应用”、“手机陶瓷背板技术趋势分析”、“信息功能陶瓷原材料的国产化”、“超小型片式电容的技术发展”等热点话题进行专题研讨，寻求各界共识。</p><p>关注新兴领域 紧握发展良机</p><p>科技创新和新兴领域的崛起对任何行业的发展都至关重要。为应对国际金融危机的中长期影响，世界各国纷纷将科技创新作为战略举措，大力发展新能源、新材料、电子信息、节能环保等新兴产业，以抢占未来科技和产业发展的制高点。电子元器件行业要把握发展机遇，取得进一步发展，需重点关注以上新应用领域及政策导向，如三网融合、无线互联、物联网、新能源及高铁建设等领域。</p><p>以物联网产业为例，传感器和敏感元器件是未来万亿元产业的基础，必定会得到国家的大力扶持。未来几年，物联网将不断渗透到社会生活中，包括智能交通、智能城市、环境保护、公共安全、智能电网等众多领域，带动电子元器件等产业高速发展。据有关研究机构预测，到2020年，物联网相关的嵌入式芯片、传感器、无线射频的“智能物件”数或超过2万亿。近日无锡物联网产业研究院战略部的韩夏在谈到“中国元件制造业在物联网时代的机会”时透露，上海浦东机场一期工程的35公里围栏建设项目，大概需要3万多个物联网节点，而每个节点均由各式传感器及其他电子器件构成，元器件整体需求非常庞大。</p><p><br/></p>','',0,0,0,0,0,1,1,0,'news/industrynews/81.html',1640419044,1640419044,1791527801,0,0,0,0,0,''),(82,69,1,'电子元器件行业如何适应时代需求','','','','','','/upload/image/20211225/82f6a51bca9d203de9882fbb97be4593.png','','','','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更</p>','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更加紧密，进而推动新一轮的产业创新浪潮。在三网融合建设中，无论是网络设备还是终端设备都离不开各种元器件。网络的改造升级、终端设备的多样化设计都要依托关键元器件技术的革新。建设高速铁路，需要现代化的路网指挥系统、现代化的高速机车，这些都和电子元器件尤其是大功率电力电子器件密不可分。随着新能源的广泛应用，对环保节能型电子元器件产品的需求也将越来越大。这都为相关的元器件企业提供了巨大的市场机遇。但是，目前我国的电子元器件产品，无论技术还是规模都不足以支撑起这些新兴产业的发展。国内的电子元器件从业者，特别是资金雄厚的大企业应该及早关注这一领域，尽早切入这些行业。</p><p>顶级专家莅临会场 明星厂商寻求共识</p><p>电子元器件及相关材料是国际高新技术领域最活跃的研究前沿和创新热点之一。为进一步推动我国电子元器件的研究和产业发展，加强政府、高校、科研机构与企业之间的沟通交流，促进新型电子元器件的技术进步与应用水平提高，进而满足我国电子信息行业飞速发展和《中国制造2025》的新要求，中国电子学会将于2017年10月26日在上海第90届中国电子展期间，召开中国电子元件与材料技术发展论坛。本届论坛将围绕“4G/5G时代的新型元器件发展与挑战”主题，针对行业前沿及应用技术，邀请国家重点研发计划“战略性电子信息材料”项目首席科学家孙蓉、中国电子元件行业协会秘书长温学礼等专家，以及广东风华高新科、深圳顺络电子、潮州三环、山东国瓷、深圳市宇阳科技、太阳诱电、京瓷、TDK等多家全球知名元件厂商和材料厂商代表进行精彩演讲。会议期间将就“国内外元件与材料发展现状与趋势”、“陶瓷介质滤波器在4G/5G通讯中的应用”、“LTCC器件在移动通信中的应用”、“手机陶瓷背板技术趋势分析”、“信息功能陶瓷原材料的国产化”、“超小型片式电容的技术发展”等热点话题进行专题研讨，寻求各界共识。</p><p>关注新兴领域 紧握发展良机</p><p>科技创新和新兴领域的崛起对任何行业的发展都至关重要。为应对国际金融危机的中长期影响，世界各国纷纷将科技创新作为战略举措，大力发展新能源、新材料、电子信息、节能环保等新兴产业，以抢占未来科技和产业发展的制高点。电子元器件行业要把握发展机遇，取得进一步发展，需重点关注以上新应用领域及政策导向，如三网融合、无线互联、物联网、新能源及高铁建设等领域。</p><p>以物联网产业为例，传感器和敏感元器件是未来万亿元产业的基础，必定会得到国家的大力扶持。未来几年，物联网将不断渗透到社会生活中，包括智能交通、智能城市、环境保护、公共安全、智能电网等众多领域，带动电子元器件等产业高速发展。据有关研究机构预测，到2020年，物联网相关的嵌入式芯片、传感器、无线射频的“智能物件”数或超过2万亿。近日无锡物联网产业研究院战略部的韩夏在谈到“中国元件制造业在物联网时代的机会”时透露，上海浦东机场一期工程的35公里围栏建设项目，大概需要3万多个物联网节点，而每个节点均由各式传感器及其他电子器件构成，元器件整体需求非常庞大。</p><p><br/></p>','',0,0,0,0,0,1,1,0,'news/industrynews/82.html',1640419044,1640419044,1791527801,0,0,0,0,0,''),(83,69,1,'电子元器件行业如何适应时代需求','','','','','','/upload/image/20211225/82f6a51bca9d203de9882fbb97be4593.png','','','','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更</p>','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更加紧密，进而推动新一轮的产业创新浪潮。在三网融合建设中，无论是网络设备还是终端设备都离不开各种元器件。网络的改造升级、终端设备的多样化设计都要依托关键元器件技术的革新。建设高速铁路，需要现代化的路网指挥系统、现代化的高速机车，这些都和电子元器件尤其是大功率电力电子器件密不可分。随着新能源的广泛应用，对环保节能型电子元器件产品的需求也将越来越大。这都为相关的元器件企业提供了巨大的市场机遇。但是，目前我国的电子元器件产品，无论技术还是规模都不足以支撑起这些新兴产业的发展。国内的电子元器件从业者，特别是资金雄厚的大企业应该及早关注这一领域，尽早切入这些行业。</p><p>顶级专家莅临会场 明星厂商寻求共识</p><p>电子元器件及相关材料是国际高新技术领域最活跃的研究前沿和创新热点之一。为进一步推动我国电子元器件的研究和产业发展，加强政府、高校、科研机构与企业之间的沟通交流，促进新型电子元器件的技术进步与应用水平提高，进而满足我国电子信息行业飞速发展和《中国制造2025》的新要求，中国电子学会将于2017年10月26日在上海第90届中国电子展期间，召开中国电子元件与材料技术发展论坛。本届论坛将围绕“4G/5G时代的新型元器件发展与挑战”主题，针对行业前沿及应用技术，邀请国家重点研发计划“战略性电子信息材料”项目首席科学家孙蓉、中国电子元件行业协会秘书长温学礼等专家，以及广东风华高新科、深圳顺络电子、潮州三环、山东国瓷、深圳市宇阳科技、太阳诱电、京瓷、TDK等多家全球知名元件厂商和材料厂商代表进行精彩演讲。会议期间将就“国内外元件与材料发展现状与趋势”、“陶瓷介质滤波器在4G/5G通讯中的应用”、“LTCC器件在移动通信中的应用”、“手机陶瓷背板技术趋势分析”、“信息功能陶瓷原材料的国产化”、“超小型片式电容的技术发展”等热点话题进行专题研讨，寻求各界共识。</p><p>关注新兴领域 紧握发展良机</p><p>科技创新和新兴领域的崛起对任何行业的发展都至关重要。为应对国际金融危机的中长期影响，世界各国纷纷将科技创新作为战略举措，大力发展新能源、新材料、电子信息、节能环保等新兴产业，以抢占未来科技和产业发展的制高点。电子元器件行业要把握发展机遇，取得进一步发展，需重点关注以上新应用领域及政策导向，如三网融合、无线互联、物联网、新能源及高铁建设等领域。</p><p>以物联网产业为例，传感器和敏感元器件是未来万亿元产业的基础，必定会得到国家的大力扶持。未来几年，物联网将不断渗透到社会生活中，包括智能交通、智能城市、环境保护、公共安全、智能电网等众多领域，带动电子元器件等产业高速发展。据有关研究机构预测，到2020年，物联网相关的嵌入式芯片、传感器、无线射频的“智能物件”数或超过2万亿。近日无锡物联网产业研究院战略部的韩夏在谈到“中国元件制造业在物联网时代的机会”时透露，上海浦东机场一期工程的35公里围栏建设项目，大概需要3万多个物联网节点，而每个节点均由各式传感器及其他电子器件构成，元器件整体需求非常庞大。</p><p><br/></p>','',0,0,0,0,0,1,0,0,'news/industrynews/83.html',1640419044,1640419044,1791527801,0,0,0,0,0,''),(84,69,1,'电子元器件行业如何适应时代需求','','','','','','/upload/image/20211225/82f6a51bca9d203de9882fbb97be4593.png','','','','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更</p>','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更加紧密，进而推动新一轮的产业创新浪潮。在三网融合建设中，无论是网络设备还是终端设备都离不开各种元器件。网络的改造升级、终端设备的多样化设计都要依托关键元器件技术的革新。建设高速铁路，需要现代化的路网指挥系统、现代化的高速机车，这些都和电子元器件尤其是大功率电力电子器件密不可分。随着新能源的广泛应用，对环保节能型电子元器件产品的需求也将越来越大。这都为相关的元器件企业提供了巨大的市场机遇。但是，目前我国的电子元器件产品，无论技术还是规模都不足以支撑起这些新兴产业的发展。国内的电子元器件从业者，特别是资金雄厚的大企业应该及早关注这一领域，尽早切入这些行业。</p><p>顶级专家莅临会场 明星厂商寻求共识</p><p>电子元器件及相关材料是国际高新技术领域最活跃的研究前沿和创新热点之一。为进一步推动我国电子元器件的研究和产业发展，加强政府、高校、科研机构与企业之间的沟通交流，促进新型电子元器件的技术进步与应用水平提高，进而满足我国电子信息行业飞速发展和《中国制造2025》的新要求，中国电子学会将于2017年10月26日在上海第90届中国电子展期间，召开中国电子元件与材料技术发展论坛。本届论坛将围绕“4G/5G时代的新型元器件发展与挑战”主题，针对行业前沿及应用技术，邀请国家重点研发计划“战略性电子信息材料”项目首席科学家孙蓉、中国电子元件行业协会秘书长温学礼等专家，以及广东风华高新科、深圳顺络电子、潮州三环、山东国瓷、深圳市宇阳科技、太阳诱电、京瓷、TDK等多家全球知名元件厂商和材料厂商代表进行精彩演讲。会议期间将就“国内外元件与材料发展现状与趋势”、“陶瓷介质滤波器在4G/5G通讯中的应用”、“LTCC器件在移动通信中的应用”、“手机陶瓷背板技术趋势分析”、“信息功能陶瓷原材料的国产化”、“超小型片式电容的技术发展”等热点话题进行专题研讨，寻求各界共识。</p><p>关注新兴领域 紧握发展良机</p><p>科技创新和新兴领域的崛起对任何行业的发展都至关重要。为应对国际金融危机的中长期影响，世界各国纷纷将科技创新作为战略举措，大力发展新能源、新材料、电子信息、节能环保等新兴产业，以抢占未来科技和产业发展的制高点。电子元器件行业要把握发展机遇，取得进一步发展，需重点关注以上新应用领域及政策导向，如三网融合、无线互联、物联网、新能源及高铁建设等领域。</p><p>以物联网产业为例，传感器和敏感元器件是未来万亿元产业的基础，必定会得到国家的大力扶持。未来几年，物联网将不断渗透到社会生活中，包括智能交通、智能城市、环境保护、公共安全、智能电网等众多领域，带动电子元器件等产业高速发展。据有关研究机构预测，到2020年，物联网相关的嵌入式芯片、传感器、无线射频的“智能物件”数或超过2万亿。近日无锡物联网产业研究院战略部的韩夏在谈到“中国元件制造业在物联网时代的机会”时透露，上海浦东机场一期工程的35公里围栏建设项目，大概需要3万多个物联网节点，而每个节点均由各式传感器及其他电子器件构成，元器件整体需求非常庞大。</p><p><br/></p>','',0,0,0,0,0,1,0,0,'news/industrynews/84.html',1640419044,1640419044,1791527802,0,0,0,0,0,''),(85,69,1,'电子元器件行业如何适应时代需求','','','','','','/upload/image/20211225/82f6a51bca9d203de9882fbb97be4593.png','','','','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更</p>','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更加紧密，进而推动新一轮的产业创新浪潮。在三网融合建设中，无论是网络设备还是终端设备都离不开各种元器件。网络的改造升级、终端设备的多样化设计都要依托关键元器件技术的革新。建设高速铁路，需要现代化的路网指挥系统、现代化的高速机车，这些都和电子元器件尤其是大功率电力电子器件密不可分。随着新能源的广泛应用，对环保节能型电子元器件产品的需求也将越来越大。这都为相关的元器件企业提供了巨大的市场机遇。但是，目前我国的电子元器件产品，无论技术还是规模都不足以支撑起这些新兴产业的发展。国内的电子元器件从业者，特别是资金雄厚的大企业应该及早关注这一领域，尽早切入这些行业。</p><p>顶级专家莅临会场 明星厂商寻求共识</p><p>电子元器件及相关材料是国际高新技术领域最活跃的研究前沿和创新热点之一。为进一步推动我国电子元器件的研究和产业发展，加强政府、高校、科研机构与企业之间的沟通交流，促进新型电子元器件的技术进步与应用水平提高，进而满足我国电子信息行业飞速发展和《中国制造2025》的新要求，中国电子学会将于2017年10月26日在上海第90届中国电子展期间，召开中国电子元件与材料技术发展论坛。本届论坛将围绕“4G/5G时代的新型元器件发展与挑战”主题，针对行业前沿及应用技术，邀请国家重点研发计划“战略性电子信息材料”项目首席科学家孙蓉、中国电子元件行业协会秘书长温学礼等专家，以及广东风华高新科、深圳顺络电子、潮州三环、山东国瓷、深圳市宇阳科技、太阳诱电、京瓷、TDK等多家全球知名元件厂商和材料厂商代表进行精彩演讲。会议期间将就“国内外元件与材料发展现状与趋势”、“陶瓷介质滤波器在4G/5G通讯中的应用”、“LTCC器件在移动通信中的应用”、“手机陶瓷背板技术趋势分析”、“信息功能陶瓷原材料的国产化”、“超小型片式电容的技术发展”等热点话题进行专题研讨，寻求各界共识。</p><p>关注新兴领域 紧握发展良机</p><p>科技创新和新兴领域的崛起对任何行业的发展都至关重要。为应对国际金融危机的中长期影响，世界各国纷纷将科技创新作为战略举措，大力发展新能源、新材料、电子信息、节能环保等新兴产业，以抢占未来科技和产业发展的制高点。电子元器件行业要把握发展机遇，取得进一步发展，需重点关注以上新应用领域及政策导向，如三网融合、无线互联、物联网、新能源及高铁建设等领域。</p><p>以物联网产业为例，传感器和敏感元器件是未来万亿元产业的基础，必定会得到国家的大力扶持。未来几年，物联网将不断渗透到社会生活中，包括智能交通、智能城市、环境保护、公共安全、智能电网等众多领域，带动电子元器件等产业高速发展。据有关研究机构预测，到2020年，物联网相关的嵌入式芯片、传感器、无线射频的“智能物件”数或超过2万亿。近日无锡物联网产业研究院战略部的韩夏在谈到“中国元件制造业在物联网时代的机会”时透露，上海浦东机场一期工程的35公里围栏建设项目，大概需要3万多个物联网节点，而每个节点均由各式传感器及其他电子器件构成，元器件整体需求非常庞大。</p><p><br/></p>','',0,0,0,0,0,1,0,0,'news/industrynews/85.html',1640419044,1640419044,1791527802,0,0,0,0,0,''),(86,69,1,'电子元器件行业如何适应时代需求','','','','','','/upload/image/20211225/82f6a51bca9d203de9882fbb97be4593.png','','','','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更</p>','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更加紧密，进而推动新一轮的产业创新浪潮。在三网融合建设中，无论是网络设备还是终端设备都离不开各种元器件。网络的改造升级、终端设备的多样化设计都要依托关键元器件技术的革新。建设高速铁路，需要现代化的路网指挥系统、现代化的高速机车，这些都和电子元器件尤其是大功率电力电子器件密不可分。随着新能源的广泛应用，对环保节能型电子元器件产品的需求也将越来越大。这都为相关的元器件企业提供了巨大的市场机遇。但是，目前我国的电子元器件产品，无论技术还是规模都不足以支撑起这些新兴产业的发展。国内的电子元器件从业者，特别是资金雄厚的大企业应该及早关注这一领域，尽早切入这些行业。</p><p>顶级专家莅临会场 明星厂商寻求共识</p><p>电子元器件及相关材料是国际高新技术领域最活跃的研究前沿和创新热点之一。为进一步推动我国电子元器件的研究和产业发展，加强政府、高校、科研机构与企业之间的沟通交流，促进新型电子元器件的技术进步与应用水平提高，进而满足我国电子信息行业飞速发展和《中国制造2025》的新要求，中国电子学会将于2017年10月26日在上海第90届中国电子展期间，召开中国电子元件与材料技术发展论坛。本届论坛将围绕“4G/5G时代的新型元器件发展与挑战”主题，针对行业前沿及应用技术，邀请国家重点研发计划“战略性电子信息材料”项目首席科学家孙蓉、中国电子元件行业协会秘书长温学礼等专家，以及广东风华高新科、深圳顺络电子、潮州三环、山东国瓷、深圳市宇阳科技、太阳诱电、京瓷、TDK等多家全球知名元件厂商和材料厂商代表进行精彩演讲。会议期间将就“国内外元件与材料发展现状与趋势”、“陶瓷介质滤波器在4G/5G通讯中的应用”、“LTCC器件在移动通信中的应用”、“手机陶瓷背板技术趋势分析”、“信息功能陶瓷原材料的国产化”、“超小型片式电容的技术发展”等热点话题进行专题研讨，寻求各界共识。</p><p>关注新兴领域 紧握发展良机</p><p>科技创新和新兴领域的崛起对任何行业的发展都至关重要。为应对国际金融危机的中长期影响，世界各国纷纷将科技创新作为战略举措，大力发展新能源、新材料、电子信息、节能环保等新兴产业，以抢占未来科技和产业发展的制高点。电子元器件行业要把握发展机遇，取得进一步发展，需重点关注以上新应用领域及政策导向，如三网融合、无线互联、物联网、新能源及高铁建设等领域。</p><p>以物联网产业为例，传感器和敏感元器件是未来万亿元产业的基础，必定会得到国家的大力扶持。未来几年，物联网将不断渗透到社会生活中，包括智能交通、智能城市、环境保护、公共安全、智能电网等众多领域，带动电子元器件等产业高速发展。据有关研究机构预测，到2020年，物联网相关的嵌入式芯片、传感器、无线射频的“智能物件”数或超过2万亿。近日无锡物联网产业研究院战略部的韩夏在谈到“中国元件制造业在物联网时代的机会”时透露，上海浦东机场一期工程的35公里围栏建设项目，大概需要3万多个物联网节点，而每个节点均由各式传感器及其他电子器件构成，元器件整体需求非常庞大。</p><p><br/></p>','',0,0,0,0,0,1,0,0,'news/industrynews/86.html',1640419044,1640419044,1791527803,0,0,0,0,0,''),(87,69,1,'可做成红色跑道、绿色球场，标线清晰不易脱落..','','','','','','/upload/image/20211225/82f6a51bca9d203de9882fbb97be4593.png','','','','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更</p>','<p>随着4G在全球实现规模商用，5G技术已成为全球移动通信产业的研发重点。5G不仅自身具有巨大的产业价值，还能带动芯片、器件、材料、软件等多种基础产业的快速发展。5G将与互联网、物联网以及工业、交通、医疗等行业应用融合地更加紧密，进而推动新一轮的产业创新浪潮。在三网融合建设中，无论是网络设备还是终端设备都离不开各种元器件。网络的改造升级、终端设备的多样化设计都要依托关键元器件技术的革新。建设高速铁路，需要现代化的路网指挥系统、现代化的高速机车，这些都和电子元器件尤其是大功率电力电子器件密不可分。随着新能源的广泛应用，对环保节能型电子元器件产品的需求也将越来越大。这都为相关的元器件企业提供了巨大的市场机遇。但是，目前我国的电子元器件产品，无论技术还是规模都不足以支撑起这些新兴产业的发展。国内的电子元器件从业者，特别是资金雄厚的大企业应该及早关注这一领域，尽早切入这些行业。</p><p>顶级专家莅临会场 明星厂商寻求共识</p><p>电子元器件及相关材料是国际高新技术领域最活跃的研究前沿和创新热点之一。为进一步推动我国电子元器件的研究和产业发展，加强政府、高校、科研机构与企业之间的沟通交流，促进新型电子元器件的技术进步与应用水平提高，进而满足我国电子信息行业飞速发展和《中国制造2025》的新要求，中国电子学会将于2017年10月26日在上海第90届中国电子展期间，召开中国电子元件与材料技术发展论坛。本届论坛将围绕“4G/5G时代的新型元器件发展与挑战”主题，针对行业前沿及应用技术，邀请国家重点研发计划“战略性电子信息材料”项目首席科学家孙蓉、中国电子元件行业协会秘书长温学礼等专家，以及广东风华高新科、深圳顺络电子、潮州三环、山东国瓷、深圳市宇阳科技、太阳诱电、京瓷、TDK等多家全球知名元件厂商和材料厂商代表进行精彩演讲。会议期间将就“国内外元件与材料发展现状与趋势”、“陶瓷介质滤波器在4G/5G通讯中的应用”、“LTCC器件在移动通信中的应用”、“手机陶瓷背板技术趋势分析”、“信息功能陶瓷原材料的国产化”、“超小型片式电容的技术发展”等热点话题进行专题研讨，寻求各界共识。</p><p>关注新兴领域 紧握发展良机</p><p>科技创新和新兴领域的崛起对任何行业的发展都至关重要。为应对国际金融危机的中长期影响，世界各国纷纷将科技创新作为战略举措，大力发展新能源、新材料、电子信息、节能环保等新兴产业，以抢占未来科技和产业发展的制高点。电子元器件行业要把握发展机遇，取得进一步发展，需重点关注以上新应用领域及政策导向，如三网融合、无线互联、物联网、新能源及高铁建设等领域。</p><p>以物联网产业为例，传感器和敏感元器件是未来万亿元产业的基础，必定会得到国家的大力扶持。未来几年，物联网将不断渗透到社会生活中，包括智能交通、智能城市、环境保护、公共安全、智能电网等众多领域，带动电子元器件等产业高速发展。据有关研究机构预测，到2020年，物联网相关的嵌入式芯片、传感器、无线射频的“智能物件”数或超过2万亿。近日无锡物联网产业研究院战略部的韩夏在谈到“中国元件制造业在物联网时代的机会”时透露，上海浦东机场一期工程的35公里围栏建设项目，大概需要3万多个物联网节点，而每个节点均由各式传感器及其他电子器件构成，元器件整体需求非常庞大。</p><p><br/></p>','',0,0,0,0,0,1,0,0,'news/industrynews/87.html',1640419044,1640419044,1791527803,0,0,0,0,0,''),(88,86,4,'产品名称的位置','','','','','','/upload/image/20260720/a28e08d0ce02f79b12924bc05bc52e25.jpg','/upload/image/20260720/a28e08d0ce02f79b12924bc05bc52e25.jpg***/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg','','传感器','体育场地工程设计施工一体化服务，采用优质原材料，严格执行国际、国家体育场地建设标准。','<p>辰宏体育提供体育场地设计、施工一体化服务，涵盖丙烯酸球场、硅PU球场、塑胶跑道、人造草坪、运动地板等各类体育场地工程。公司拥有经验丰富、技术一流的施工队伍，采用优质原材料，严格执行国际、国家体育场地建设标准，为客户营造舒适、安全的运动空间。</p><p>欢迎来电咨询：15242413419</p>','{\"piclist\":[\"/upload/image/20260720/a28e08d0ce02f79b12924bc05bc52e25.jpg\",\"/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg\"]}',0,0,0,0,0,1,4,0,'pro/pro1/1/88.html',1640419216,1640419216,1791527811,0,0,0,0,0,''),(89,69,1,'回弹力度适中，跑步蹬地省力，跳远、跳高落地缓冲..','','','','','','/upload/image/20211225/82f6a51bca9d203de9882fbb97be4593.png','','','','','','',0,0,0,0,0,1,0,0,'news/industrynews/89.html',1640419044,1640419044,1791527804,0,0,0,0,0,''),(90,69,1,'排水结构完善，雨后快速排水无大面积积水..','','','','','','/upload/image/20211225/82f6a51bca9d203de9882fbb97be4593.png','','','','','','',0,0,0,0,0,1,0,0,'news/industrynews/90.html',1640419044,1640419044,1791527804,0,0,0,0,0,''),(91,69,1,'塑胶有弹性层，跑步、跳跃落地时吸收冲击力..','','','','','','/upload/image/20211225/82f6a51bca9d203de9882fbb97be4593.png','','','','','','',0,0,0,0,0,1,3,0,'news/industrynews/91.html',1640419044,1640419044,1791527805,0,0,0,0,0,''),(92,63,3,'应用案例','','','','','','/upload/image/20220104/baff6335bc0fffc921423830405a5905.jpg','','','','','','',0,0,0,0,0,1,0,0,'case/92.html',1641293484,1641293484,1791527820,0,0,0,0,0,''),(93,63,3,'应用案例','','','','','','/upload/image/20220104/baff6335bc0fffc921423830405a5905.jpg','','','','','','',0,0,0,0,0,1,0,0,'case/93.html',1641293484,1641293484,1791527821,0,0,0,0,0,''),(94,63,3,'应用案例','','','','','','/upload/image/20220104/baff6335bc0fffc921423830405a5905.jpg','','','','','','',0,0,0,0,0,1,0,0,'case/94.html',1641293484,1641293484,1791527821,0,0,0,0,0,''),(95,63,3,'应用案例','','','','','','/upload/image/20220104/baff6335bc0fffc921423830405a5905.jpg','','','','','','',0,0,0,0,0,1,0,0,'case/95.html',1641293484,1641293484,1791527822,0,0,0,0,0,''),(96,63,3,'应用案例','','','','','','/upload/image/20220104/baff6335bc0fffc921423830405a5905.jpg','','','','','','',0,0,0,0,0,1,0,0,'case/96.html',1641293484,1641293484,1791527822,0,0,0,0,0,''),(97,63,3,'应用案例','','','','','','/upload/image/20220104/baff6335bc0fffc921423830405a5905.jpg','','','','','','',0,0,0,0,0,1,0,0,'case/97.html',1641293484,1641293484,1791527823,0,0,0,0,0,''),(98,63,3,'应用案例','','','','','','/upload/image/20220104/baff6335bc0fffc921423830405a5905.jpg','','','','','','',0,0,0,0,0,1,0,0,'case/98.html',1641293484,1641293484,1791527823,0,0,0,0,0,''),(99,63,3,'应用案例','','','','','','/upload/image/20220104/baff6335bc0fffc921423830405a5905.jpg','','','','','','',0,0,0,0,0,1,0,0,'case/99.html',1641293484,1641293484,1791527824,0,0,0,0,0,''),(100,86,4,'产品名称的位置','','','','','','/upload/image/20260720/3c06eca08bc7b9c3de8cfeff6e469211.jpg','/upload/image/20260720/3c06eca08bc7b9c3de8cfeff6e469211.jpg***/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg','','传感器','体育场地工程设计施工一体化服务，采用优质原材料，严格执行国际、国家体育场地建设标准。','<p>辰宏体育提供体育场地设计、施工一体化服务，涵盖丙烯酸球场、硅PU球场、塑胶跑道、人造草坪、运动地板等各类体育场地工程。公司拥有经验丰富、技术一流的施工队伍，采用优质原材料，严格执行国际、国家体育场地建设标准，为客户营造舒适、安全的运动空间。</p><p>欢迎来电咨询：15242413419</p>','{\"piclist\":[\"/upload/image/20260720/3c06eca08bc7b9c3de8cfeff6e469211.jpg\",\"/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg\"]}',0,0,0,0,0,1,4,0,'pro/pro1/1/100.html',1640419216,1640419216,1791527812,0,0,0,0,0,''),(101,86,4,'本溪市牛心台小学','','','','','','/upload/image/20260720/33ea2b015a834ff63370df17793285e4.jpg','/upload/image/20260720/33ea2b015a834ff63370df17793285e4.jpg***/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg***/upload/image/20260722/2c7129b43d321f0538ca43012b9e5d4e.jpg','','传感器','本溪市牛心台小学体育场地工程，辰宏体育承建，环保材料与专业施工保障学生运动安全。','<p>本溪市牛心台小学体育场地工程由沈阳辰宏体育设施有限公司承建。项目选用环保优质原材料，按照国家体育场地建设标准施工，为师生打造安全、舒适的运动环境。</p><p>辰宏体育始终贯彻“质量是企业生存之本，客户的需求是公司发展的永恒主题”的质量方针，致力于让每一寸运动场地都经得起时间与使用的考验。</p>','{\"piclist\":[\"/upload/image/20260720/33ea2b015a834ff63370df17793285e4.jpg\",\"/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg\",\"/upload/image/20260722/2c7129b43d321f0538ca43012b9e5d4e.jpg\"]}',0,0,0,0,0,1,4,0,'pro/pro1/1/101.html',1640419216,1640419216,1791527813,0,0,0,0,0,''),(102,86,4,'沈阳东北制药','','','','','','/upload/image/20260720/5126a25a9109f4f9b988965dee8c5734.jpg','/upload/image/20260720/5126a25a9109f4f9b988965dee8c5734.jpg***/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg***/upload/image/20260720/bf1d8d9a15f0f19afc6195a7dd6e7114.jpg','','传感器','沈阳东北制药体育场地工程，辰宏体育承建，高标准施工满足企业职工运动健身需求。','<p>沈阳东北制药体育场地工程由沈阳辰宏体育设施有限公司承建。项目涵盖企业园区运动场地建设，采用优质材料与专业施工工艺，满足职工日常运动健身需求。</p><p>辰宏体育以“质量、服务为根本，诚信经营为基础，共赢互利为目标”的经营方针，竭诚为社会及广大客户服务。</p>','{\"piclist\":[\"/upload/image/20260720/5126a25a9109f4f9b988965dee8c5734.jpg\",\"/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg\",\"/upload/image/20260720/bf1d8d9a15f0f19afc6195a7dd6e7114.jpg\"]}',0,0,0,0,0,1,4,0,'pro/pro1/1/102.html',1640419216,1640419216,1791527813,0,0,0,0,0,''),(103,86,4,'产品名称的位置','','','','','','/upload/image/20260720/65a9e2bcc374836ff95a011dc7ad7ddb.jpg','/upload/image/20260720/65a9e2bcc374836ff95a011dc7ad7ddb.jpg***/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg','','传感器','体育场地工程设计施工一体化服务，采用优质原材料，严格执行国际、国家体育场地建设标准。','<p>辰宏体育提供体育场地设计、施工一体化服务，涵盖丙烯酸球场、硅PU球场、塑胶跑道、人造草坪、运动地板等各类体育场地工程。公司拥有经验丰富、技术一流的施工队伍，采用优质原材料，严格执行国际、国家体育场地建设标准，为客户营造舒适、安全的运动空间。</p><p>欢迎来电咨询：15242413419</p>','{\"piclist\":[\"/upload/image/20260720/65a9e2bcc374836ff95a011dc7ad7ddb.jpg\",\"/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg\"]}',0,0,0,0,0,1,4,0,'pro/pro1/1/103.html',1640419216,1640419216,1791527814,0,0,0,0,0,''),(104,86,4,'产品名称的位置','','','','','','/upload/image/20260720/7ab2e61419e16bc699bb301dafdd2da4.jpg','/upload/image/20260720/7ab2e61419e16bc699bb301dafdd2da4.jpg***/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg','','传感器','体育场地工程设计施工一体化服务，采用优质原材料，严格执行国际、国家体育场地建设标准。','<p>辰宏体育提供体育场地设计、施工一体化服务，涵盖丙烯酸球场、硅PU球场、塑胶跑道、人造草坪、运动地板等各类体育场地工程。公司拥有经验丰富、技术一流的施工队伍，采用优质原材料，严格执行国际、国家体育场地建设标准，为客户营造舒适、安全的运动空间。</p><p>欢迎来电咨询：15242413419</p>','{\"piclist\":[\"/upload/image/20260720/7ab2e61419e16bc699bb301dafdd2da4.jpg\",\"/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg\"]}',0,0,0,0,0,1,4,0,'pro/pro1/1/104.html',1640419216,1640419216,1791527815,0,0,0,0,0,''),(105,86,4,'苏家屯华夏','','','','','','/upload/image/20260720/da5866c22ed376f50508c8f5c4daf4ba.jpg','/upload/image/20260720/da5866c22ed376f50508c8f5c4daf4ba.jpg***/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg***/upload/image/20260722/2c7129b43d321f0538ca43012b9e5d4e.jpg','','传感器','苏家屯华夏体育场地工程，辰宏体育承建，优质材料专业施工营造舒适运动空间。','<p>苏家屯华夏体育场地工程由沈阳辰宏体育设施有限公司承建。辰宏体育以“致力于体育场地建设，创造舒适的运动空间”为宗旨，选用优质原材料，严格按照国家标准施工，为客户提供专业的体育场地解决方案。</p>','{\"piclist\":[\"/upload/image/20260720/da5866c22ed376f50508c8f5c4daf4ba.jpg\",\"/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg\",\"/upload/image/20260722/2c7129b43d321f0538ca43012b9e5d4e.jpg\"]}',0,0,0,0,0,1,4,0,'pro/pro1/1/105.html',1640419216,1640419216,1791527815,0,0,0,0,0,''),(106,86,4,'浑河西峡谷','','','','','','/upload/image/20260720/34581df9a568320f968eeb086544f615.jpg','/upload/image/20260720/34581df9a568320f968eeb086544f615.jpg***/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg***/upload/image/20260722/2c7129b43d321f0538ca43012b9e5d4e.jpg','','传感器','浑河西峡谷体育场地工程，辰宏体育承建，专业施工团队打造高品质运动场地。','<p>浑河西峡谷体育场地工程由沈阳辰宏体育设施有限公司承建。项目充分发挥辰宏体育在体育场地施工领域的专业优势，涵盖场地基础处理、面层铺设、划线验收等全流程服务。</p><p>公司拥有经验丰富、技术一流的施工队伍和先进设备，始终坚持“质量是企业生存之本”的方针，为客户交付高品质运动空间。</p>','{\"piclist\":[\"/upload/image/20260720/34581df9a568320f968eeb086544f615.jpg\",\"/upload/image/20260722/a642507a37f48eb0c24318fdd7fea75e.jpg\",\"/upload/image/20260722/2c7129b43d321f0538ca43012b9e5d4e.jpg\"]}',0,0,0,0,0,1,13,0,'pro/pro1/1/106.html',1640419216,1640419216,1791527816,0,0,0,0,0,'');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友情链接-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_link`
--

LOCK TABLES `yy_cn_link` WRITE;
/*!40000 ALTER TABLE `yy_cn_link` DISABLE KEYS */;
INSERT INTO `yy_cn_link` VALUES (5,'百度','http://www.baidu.com','/upload/image/20180409/40259f772670f77305630fb439260bea.jpg','',1,1,1558579836,1791461938);
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='留言-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_message`
--

LOCK TABLES `yy_cn_message` WRITE;
/*!40000 ALTER TABLE `yy_cn_message` DISABLE KEYS */;
INSERT INTO `yy_cn_message` VALUES (1,1,'1','2','3','4','5','789','127.0.0.1',1,1,0,'',1791445409,1791445379,1791445409,''),(2,1,'测试用户','13800000000','','','这是一条测试公开留言内容',NULL,'127.0.0.1',1,0,0,'ENTEJ436',0,1791446461,1791446522,''),(3,1,'公开测试用户','13800000001','','','公开留言内容测试','公开回复内容：感谢留言！','127.0.0.1',1,1,1,'PGVXC8GX',1791446635,1791446635,1791446635,''),(4,1,'私密用户','13800000002','','','私密留言不公开',NULL,'127.0.0.1',1,0,1,'7KJ44BNN',0,1791446635,1791446845,''),(5,1,'7','8','9','10','11','0000789','127.0.0.1',1,1,1,'4XD3Q5LK',1791447355,1791447187,1791447355,''),(6,1,'公开选择测试','','','','前台选公开',NULL,'127.0.0.1',1,0,1,'SGHUSGRN',0,1791448112,1791448141,''),(7,1,'不公开选择测试','','','','前台选不公开',NULL,'127.0.0.1',0,0,0,'UH8XKZKT',0,1791448112,1791448112,''),(8,1,'默认不公开测试','','','','默认不公开',NULL,'127.0.0.1',1,0,0,'EZ4GLAZ7',0,1791448113,1791450103,'');
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
INSERT INTO `yy_cn_model` VALUES (1,'article','文章模型','通用图文文章','list.html','show.html',NULL,1,1790238137,1790238137),(2,'product','产品模型','企业产品展示，含缩略图/价格字段','product_list.html','product_show.html',NULL,1,1790238137,1790238137),(3,'picture','图片模型','图片集展示','picture_list.html','picture_show.html',NULL,1,1790238137,1790238137),(4,'productdiy','DIY产品','','list','show','',1,1791461838,1791461838);
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='语言设置-cn';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_cn_setting`
--

LOCK TABLES `yy_cn_setting` WRITE;
/*!40000 ALTER TABLE `yy_cn_setting` DISABLE KEYS */;
INSERT INTO `yy_cn_setting` VALUES (1,'site.name','沈阳辰宏体育设施有限公司',0,0),(2,'site.url','http://localhost',0,0),(3,'site.logo','/upload/image/20260720/logo_c05f12df.png',0,0),(4,'site.icp','',0,0),(5,'site.keywords','易云CMS,企业建站,内容管理系统',0,0),(6,'site.description','易云CMS 是一套基于 ThinkPHP 6 的现代化企业建站内容管理系统。',0,0),(7,'site.copyright','Copyright ©2026 版权所有 沈阳辰宏体育设施有限公司 <a href=\"https://beian.miit.gov.cn/\" rel=\"nofollow\" target=\"_blank\">辽ICP（备）123456789-7 号</a>',0,0),(8,'site.close','0',0,0),(9,'site.close_tip','站点维护中，请稍后再访问',0,0),(10,'seo.title','建筑工程施工_给排水工程_市政园林绿化工程-沈阳辰宏体育设施有限公司',0,0),(11,'seo.keywords','建筑工程施工,给排水工程,市政园林绿化工程,体育场馆设施,体育场馆设计,体育场馆施工,技术咨询,翻修及维护工程,防水保温工程,弱电工程设计,弱电工程施工,照明器材,设计施工,安装及维护,体育设备租赁,体育赛事活动策划',0,0),(12,'seo.description','辰宏体育是一家经营体育用品、健身器材，承建体育场地工程的专业性公司。公司以“致力于体育场地建设，创造舒适的运动空间”为宗旨，本着“质量、服务为根本，诚信经营为基础，共赢互利为目标”的经营方针竭诚为社会及广大客户服务。',0,0),(13,'message.audit','1',0,1791433901),(14,'message.notify','0',0,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='广告位-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_ad`
--

LOCK TABLES `yy_en_ad` WRITE;
/*!40000 ALTER TABLE `yy_en_ad` DISABLE KEYS */;
INSERT INTO `yy_en_ad` VALUES (1,0,'home_banner','Home Banner',1920,600,'[{\"image\":\"\",\"title\":\"YouYun CMS makes website building easy\",\"link\":\"\"}]','','',100,1,1790238137,1790238137),(2,0,'side_ad','Side Ad',300,250,'','','',100,1,1790238137,1790238137);
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
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='栏目-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_category`
--

LOCK TABLES `yy_en_category` WRITE;
/*!40000 ALTER TABLE `yy_en_category` DISABLE KEYS */;
INSERT INTO `yy_en_category` VALUES (1,0,1,0,'About Us','about','','single',0,'',0,'About Us','','','',NULL,NULL,'','','en/about.html',1,1,1,10,1790238137,1790749602),(2,0,1,0,'News','news','','list',0,'',0,'','','','',NULL,NULL,'','','en/news',1,1,100,10,0,1791453509),(3,0,2,0,'Products','product','','list',0,'',0,'Products','','','',NULL,NULL,'','','en/product',1,1,3,10,1790238137,1790749679),(4,0,1,0,'Contact','contact','','single',0,'',0,'Contact','','','',NULL,NULL,'','','en/contact.html',1,1,4,10,1790238137,1790749680),(13,0,1,0,'企业优势','solution','','redirect',0,'@17',0,'','','','','',NULL,'','','',0,0,0,10,1790759862,1790759862),(14,13,1,0,'企业简介','solution2','','redirect',0,'@7',0,'','','','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','',NULL,'','','en/solution/solution2',1,1,0,10,1790759863,1791453508),(15,13,1,0,'发展目标','solution3','','redirect',0,'@7',0,'','','','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','',NULL,'','','en/solution/solution3',1,1,0,10,1790759863,1791453508),(16,13,1,0,'经营理念','solution4','','redirect',0,'@7',0,'','','','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','',NULL,'','','en/solution/solution4',1,1,0,10,1790759863,1791453508),(17,13,1,0,'环境展示','solution5','','redirect',0,'@7',0,'','','','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','',NULL,'','','en/solution/solution5',1,1,0,10,1790759863,1791453508),(18,13,1,0,'专业塑造品质，服务创造未来','solution6','','redirect',0,'@7',0,'','','','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','',NULL,'','','en/solution/solution6',1,1,0,10,1790759863,1791453508),(19,13,1,0,'厂家直销，同等价格质量更好','solution7','','redirect',0,'@7',0,'','','','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','',NULL,'','','en/solution/solution7',1,1,0,10,1790759863,1791453508),(20,13,1,0,'拥有多位高素质、高水准的技术人才','solution1','','redirect',0,'@7',0,'','','','/upload/image/20260720/2c7129b43d321f0538ca43012b9e5d4e.jpg','<p>方案描述</p><p>随着数据中心和5G商用网络的大规模建设，对于高速模块的需求量会越来越大。目前进入批量生产的高速光模块主要有：</p><p>● 400G速率：QSFP/QSFP-DD</p><p>● 200G速率：QSFP/QSFP-DD</p><p>● 100G速率：QSFP28/CFP4/CFP2/CFP/CXP</p><p>● 25G速率：SFP28</p><p>● 10G速率：SFP+/XFP</p><p>光模块测试面临的挑战</p><p>● 光模块测试项目多，测试复杂</p><p>● 整个测试占用大量的时间与人力</p><p>● 测量设备费用高昂，测试成本高</p>',NULL,'','','en/solution/solution1',1,0,7,10,1790759863,1791453509),(21,0,1,0,'公司简介','aboutus','','redirect',0,'@7',0,'','','','/upload/image/20260722/e6387987bf9cf110ca931fc1f5d3d9ee.jpg','',NULL,'','','en/aboutus',1,1,66,10,1790759887,1791453509),(22,21,1,0,'公司简介','about','','single',0,'',0,'','','','','<p>辰宏体育是一家经营体育用品、健身器材，承建体育场地工程的专业性公司。公司以“致力于体育场地建设，创造舒适的运动空间”为宗旨，本着“质量、服务为根本，诚信经营为基础，共赢互利为目标”的经营方针竭诚为社会及广大客户服务。</p><p>公司以“团结进取、开拓创新”的企业精神，并借鉴先进管理思想，逐步提高公司员工的业务水平和服务、质量意识，精心打造了一个业务精湛、服务完善的优秀团队和一支经验丰富、技术一流、设备先进的施工队伍。公司始终贯彻“质量是企业生存和发展的根本，持续改进产品及服务质量以满足广大客户的需求是公司发展的永恒主题”的质量方针，采用优质的原材料，严格执行国际、国家体育场地建造标准，为广大客户打造了一个又一个美丽舒适的运动空间。</p><p>经营范围：</p><p>承建：丙烯酸，硅PU体育场地；塑胶跑道；塑胶球场；人造草坪以及游泳池；休闲高尔夫；壁球场等体育场地；</p><p>销售：体育用品；健身器材；健身路径；塑胶跑道；人造草坪；丙烯酸球场涂料；PVC运动、商业、医疗地板；壁球馆批荡料；乳胶漆等铺设材料。</p><p>客户满意是我们工作的目标，也是衡量我们工作好坏的标准。辰宏人将一如既往地以优质的产品、良好的信誉、全面完善的服务寻求与广大客户的合作！</p>',NULL,'','','en/aboutus/about.html',1,1,0,10,1790759887,1791453509),(23,21,1,0,'企业文化','culture','','single',0,'',0,'','','','','<p>资料准备中...</p>',NULL,'','','en/aboutus/culture.html',1,1,0,10,1790759887,1791453509);
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
  `publish_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发布时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
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
  KEY `idx_publish` (`publish_time`)
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友情链接-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_link`
--

LOCK TABLES `yy_en_link` WRITE;
/*!40000 ALTER TABLE `yy_en_link` DISABLE KEYS */;
INSERT INTO `yy_en_link` VALUES (1,'ThinkPHP Official','https://www.thinkphp.cn','','',1,1,1790238137,1790238137),(2,'Example Link','https://example.com','','',2,1,1790238137,1790238137);
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
INSERT INTO `yy_en_model` VALUES (1,'article','Article','General articles','list.html','show.html',NULL,1,1790238137,1790238137),(2,'product','Product','Product showcase','product_list.html','product_show.html',NULL,1,1790238137,1790238137),(3,'picture','Picture','Picture gallery','picture_list.html','picture_show.html',NULL,1,1790238137,1790238137);
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='语言设置-en';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_en_setting`
--

LOCK TABLES `yy_en_setting` WRITE;
/*!40000 ALTER TABLE `yy_en_setting` DISABLE KEYS */;
INSERT INTO `yy_en_setting` VALUES (1,'site.name','YouYun CMS',0,0),(2,'site.url','http://localhost',0,0),(3,'site.logo','',0,0),(4,'site.icp','',0,0),(5,'site.keywords','YouYun CMS,enterprise,website',0,0),(6,'site.description','YouYun CMS is a modern enterprise website CMS built on ThinkPHP 6.',0,0),(7,'site.copyright','Copyright © 2026 YouYun CMS All Rights Reserved.',0,0),(8,'site.close','0',0,0),(9,'site.close_tip','Site under maintenance, please visit later.',0,0),(10,'seo.title','YouYun CMS - Making Website Building Easy',0,0),(11,'seo.keywords','YouYun CMS,enterprise',0,0),(12,'seo.description','YouYun CMS enterprise website content management system',0,0),(13,'message.audit','1',0,0),(14,'message.notify','0',0,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=utf8mb4 COMMENT='前台访问统计';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `yy_visit_log`
--

LOCK TABLES `yy_visit_log` WRITE;
/*!40000 ALTER TABLE `yy_visit_log` DISABLE KEYS */;
INSERT INTO `yy_visit_log` VALUES (1,'http://newcms.com/','http://newcms.com/admin/visit/index','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791443203),(2,'/','http://newcms.com/admin/visit/index','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791443203),(3,'http://newcms.com/show/index?id=45','http://newcms.com/admin/content/index','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791444453),(4,'/show/index?id=45','http://newcms.com/admin/content/index','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791444454),(5,'http://newcms.com/en/index/index','http://newcms.com/en/about.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','en',1791445350),(6,'/en/index/index','http://newcms.com/en/about.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791445351),(7,'http://newcms.com/','http://newcms.com/en/index/index','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791445366),(8,'/','http://newcms.com/en/index/index','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791445366),(9,'http://newcms.com/message/board','http://newcms.com/contact.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791445369),(10,'http://newcms.com/message/list?board_id=1&limit=5','','','','本地/内网','','pc','','127.0.0.1','cn',1791446461),(11,'http://newcms.com/message/board','http://newcms.com/contactus/message.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791447010),(12,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/contactus/message.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791447178),(13,'/contactus/message.html','http://newcms.com/contactus/contact.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791447178),(14,'http://newcms.com/message/board','http://newcms.com/contactus/message.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791447358),(15,'http://newcms.com/lists?id=12','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791450952),(16,'/lists?id=12','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791450953),(17,'http://newcms.com/show/index?id=16','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791451046),(18,'/show/index?id=16','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791451046),(19,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/show/index?id=16','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791451046),(20,'http://newcms.com/lists?id=12','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791452600),(21,'/lists?id=12','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791452600),(22,'http://newcms.com/','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791453534),(23,'http://newcms.com/cn/index.html','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791453534),(24,'http://newcms.com/lists?id=1','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791454958),(25,'http://newcms.com/lists?id=9','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791455017),(26,'http://newcms.com/lists?id=9&page=1','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791456044),(27,'http://newcms.com/lists?id=27&page=1','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791456304),(28,'http://newcms.com/lists?id=27&page=2','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791456304),(29,'http://newcms.com/lists?id=27&page=9','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791456304),(30,'http://newcms.com/lists?id=27&page=5','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791456494),(31,'http://newcms.com/lists?id=27&page=7','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791456494),(32,'http://newcms.com/lists?id=9&page=2','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791456541),(33,'http://newcms.com/lists/more?cid=9&page=2&size=5','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791457130),(34,'http://newcms.com/lists/more?cid=9&page=99&size=5','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791457131),(35,'http://newcms.com/lists/more?cid=9&page=3&size=5','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791457158),(36,'http://newcms.com/lists/more?cid=9&page=4&size=5','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791457158),(37,'http://newcms.com/lists/more?cid=9&page=5&size=5','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791457158),(38,'http://newcms.com/lists/more?cid=9&page=6&size=5','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791457158),(39,'http://newcms.com/lists?id=9','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791457200),(40,'/lists?id=9','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791457214),(41,'http://newcms.com/lists?id=9&_t=1791457248','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791457249),(42,'/lists?id=9&_t=1791457248','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791457249),(43,'http://newcms.com/lists?id=9&_r=1791457480','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791457480),(44,'/lists?id=9&_r=1791457480','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791457480),(45,'http://newcms.com/lists/more?cid=9&offset=10&size=5','http://newcms.com/lists?id=9&_r=1791457480','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791457482),(46,'http://newcms.com/lists?id=9','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791457538),(47,'http://newcms.com/','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791459281),(48,'/','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791459281),(49,'http://newcms.com/','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791459605),(50,'/','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791459605),(51,'http://newcms.com/','','','','本地/内网','其他','pc','Windows','127.0.0.1','cn',1791461949),(52,'http://newcms.com/lists?id=33','','','','本地/内网','其他','pc','Windows','127.0.0.1','cn',1791462074),(53,'http://newcms.com/lists?id=32','','','','本地/内网','其他','pc','Windows','127.0.0.1','cn',1791462081),(54,'http://newcms.com/lists?id=39','','','','本地/内网','其他','pc','Windows','127.0.0.1','cn',1791462081),(55,'http://newcms.com/search/index?kw=%E7%94%B5%E5%AD%90','','','','本地/内网','其他','pc','Windows','127.0.0.1','cn',1791462081),(56,'http://newcms.com/search/index?kw=电子','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791462089),(57,'http://newcms.com/tag/index?title=电子','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791462089),(58,'http://newcms.com/','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791462275),(59,'http://newcms.com/search/index?kw=电子','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791462406),(60,'http://newcms.com/search/index?kw=%E7%94%B5%E5%AD%90','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791462436),(61,'http://newcms.com/','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791462579),(62,'http://newcms.com/tag/index?title=电子','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791462580),(63,'http://newcms.com/lists?id=32','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791462635),(64,'http://newcms.com/lists?id=33','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791462635),(65,'http://newcms.com/lists?id=39','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791462635),(66,'http://newcms.com/show/index?id=80','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791462635),(67,'http://newcms.com/lists?id=60','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791462841),(68,'http://newcms.com/lists?id=61','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791462841),(69,'http://newcms.com/lists?id=59','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791462842),(70,'http://newcms.com/','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791463025),(71,'http://newcms.com/lists?id=63','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791463026),(72,'http://newcms.com/lists?id=69','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791463026),(73,'http://newcms.com/lists?id=67','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791463026),(74,'http://newcms.com/show/index?id=80','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791463027),(75,'http://newcms.com/search/index?kw=电子','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791463027),(76,'http://newcms.com/tag/index?title=电子','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791463028),(77,'http://newcms.com/','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791463507),(78,'http://newcms.com/lists?id=60','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791463507),(79,'http://newcms.com/show/index?id=80','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791463507),(80,'http://newcms.com/search/index?kw=电子','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791463508),(81,'http://newcms.com/tag/index?title=电子','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791463508),(82,'http://newcms.com/','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791506481),(83,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/news/industrynews/91.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791506584),(84,'http://newcms.com/','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791506825),(85,'http://newcms.com/show/index?id=106','http://newcms.com/admin/content/index','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791507073),(86,'http://newcms.com/show/Array','http://newcms.com/show/index?id=106','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791507074),(87,'http://newcms.com/lists?id=60','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791507174),(88,'http://newcms.com/','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791507531),(89,'http://newcms.com/lists?id=60','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791507532),(90,'http://newcms.com/show/index?id=80','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791507532),(91,'http://newcms.com/search/index?kw=电子','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791507532),(92,'http://newcms.com/tag/index?title=电子','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791507533),(93,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/news/industrynews/91.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791507617),(94,'http://newcms.com/','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791507936),(95,'http://newcms.com/','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791508668),(96,'http://newcms.com/','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791508996),(97,'http://newcms.com/','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791510455),(98,'http://newcms.com/message/captcha','http://newcms.com/pro/pro1/1/106.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791510561),(99,'http://newcms.com/message/captcha','http://newcms.com/pro/pro1/1/106.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791511174),(100,'http://newcms.com/message/captcha','http://newcms.com/pro/pro1/1/106.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791511174),(101,'http://newcms.com/','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791511220),(102,'http://newcms.com/message/board','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791511527),(103,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791511527),(104,'http://newcms.com/message/captcha','http://newcms.com/pro/pro1/1/106.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791511822),(105,'http://newcms.com/lists?id=9','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791511880),(106,'http://newcms.com/index.php?s=/lists&id=9','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791511892),(107,'http://newcms.com/','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791511892),(108,'http://newcms.com/index.php?s=/lists&id=67','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791511907),(109,'http://newcms.com/index.php?s=/lists&id=72','','','','本地/内网','爬虫','spider','其他','127.0.0.1','cn',1791511907),(110,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791512057),(111,'http://newcms.com/message/board','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791512057),(112,'http://newcms.com/message/captcha','http://newcms.com/pro/pro1/1/103.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791512258),(113,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791512716),(114,'http://newcms.com/message/board','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791512716),(115,'http://newcms.com/','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791513257),(116,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/contactus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791513269),(117,'http://newcms.com/message/board','http://newcms.com/contactus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791513269),(118,'http://newcms.com/','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791513557),(119,'http://newcms.com/message/captcha','http://newcms.com/pro/pro1/1/106.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791513705),(120,'http://newcms.com/message/board','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791513886),(121,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791513886),(122,'http://newcms.com/','http://newcms.com/contactus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791513889),(123,'http://newcms.com/message/captcha','http://newcms.com/pro/pro1/1/106.html?_r=1791514013793','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791514014),(124,'http://newcms.com/','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791514241),(125,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791514243),(126,'http://newcms.com/message/board','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791514243),(127,'http://newcms.com/message/captcha','http://newcms.com/pro/pro1/1/106.html?_r=1791514641424','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791514641),(128,'http://newcms.com/message/captcha','http://newcms.com/pro/pro1/1/105.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791515709),(129,'http://newcms.com/show/index?id=106','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791515763),(130,'http://newcms.com/lists?id=86','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791516039),(131,'http://newcms.com/lists?id=86&page=2','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791516040),(132,'http://newcms.com/message/captcha','http://newcms.com/pro/pro1/1/100.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791516365),(133,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/contactus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791516385),(134,'http://newcms.com/message/board','http://newcms.com/contactus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791516385),(135,'http://newcms.com/message/board','http://newcms.com/message.html?_r=1791516746007','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791516746),(136,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/message.html?_r=1791516746007','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791516746),(137,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791524820),(138,'http://newcms.com/message/board','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791524820),(139,'http://newcms.com/message/captcha','http://newcms.com/pro/pro1/1/106.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791524835),(140,'http://newcms.com/','','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791526395),(141,'http://newcms.com/message/board','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791526576),(142,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791526576),(143,'http://newcms.com/search?kw=%E6%B5%91%E6%B2%B3%E8%A5%BF%E5%B3%A1%E8%B0%B7','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791526711),(144,'http://newcms.com/search?kw=%E4%BC%A0%E6%84%9F%E5%99%A8','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791526711),(145,'http://newcms.com/search?kw=%E4%BD%93%E8%82%B2','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791526711),(146,'http://newcms.com/','','','','本地/内网','其他','pc','其他','127.0.0.1','cn',1791526711),(147,'http://newcms.com/message/captcha','http://newcms.com/pro/pro1/1/106.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791527249),(148,'http://newcms.com/search/index?kw=%E6%B5%91%E6%B2%B3','http://newcms.com/pro/pro1/1/106.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791527256),(149,'http://newcms.com/','http://newcms.com/search/index?kw=%E6%B5%91%E6%B2%B3','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791527258),(150,'http://newcms.com/message/board','http://newcms.com/aboutus.html','','','本地/内网','Chrome','mobile','Android','127.0.0.1','cn',1791527569),(151,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/aboutus.html','','','本地/内网','Chrome','mobile','Android','127.0.0.1','cn',1791527569),(152,'http://newcms.com/','http://newcms.com/pro/','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791527585),(153,'http://newcms.com/','http://newcms.com/','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791529847),(154,'http://newcms.com/message/board','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791529853),(155,'http://newcms.com/message/list?board_id=1&limit=10','http://newcms.com/aboutus.html','','','本地/内网','Chrome','pc','Windows','127.0.0.1','cn',1791529853);
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

-- Dump completed on 2026-10-09 16:26:28
