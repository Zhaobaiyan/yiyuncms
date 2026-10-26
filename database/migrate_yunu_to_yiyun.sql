-- ============================================================
-- 易云CMS 数据迁移：从云优CMS(chty 库, yunu_ 前缀)导入到易云语言表(yy_cn_*)
-- 迁移范围：栏目 / 内容(含 DIY 正文) / 广告(banner) / 自定义块 / 友情链接
-- 说明：云优栏目 etitle 为路径形式(如 news/industrynews)，取 basename 作为易云 ename
-- ============================================================
USE newcms;

-- ---------- 1. 栏目 yunu_category -> yy_cn_category ----------
TRUNCATE TABLE yy_cn_category;
INSERT INTO yy_cn_category
  (id, parent_id, model_id, name, ename, type, external_url,
   seo_title, keywords, description, image, content, template, content_template,
   static_path, is_show, is_nav, sort, create_time, update_time)
SELECT
  yc.id,
  IFNULL(yc.pid,0),
  CASE yc.mid WHEN 33 THEN 1 WHEN 34 THEN 2 WHEN 35 THEN 3 WHEN 40 THEN 2 ELSE 1 END AS model_id,
  IFNULL(yc.title,'') AS name,
  CASE WHEN SUBSTRING_INDEX(IFNULL(yc.etitle,''),'/',-1)='' THEN CONCAT('cat',yc.id)
       ELSE SUBSTRING_INDEX(yc.etitle,'/',-1) END AS ename,
  CASE WHEN IFNULL(yc.jumpurl,'')<>'' THEN 'redirect' WHEN yc.mid=37 THEN 'single' ELSE 'list' END AS type,
  IFNULL(yc.jumpurl,'') AS external_url,
  IFNULL(yc.seo_title,''), IFNULL(yc.seo_keywords,''), IFNULL(yc.seo_desc,''),
  IFNULL(yc.pic,''), IFNULL(yc.content,''),
  IFNULL(yc.tpl_list,''), IFNULL(yc.tpl_show,''),
  '', IFNULL(yc.status,1), IFNULL(yc.nav,0), IFNULL(yc.sort,100), UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM chty.yunu_category yc;

-- ---------- 2. 内容 yunu_content + DIY 正文 -> yy_cn_content ----------
TRUNCATE TABLE yy_cn_content;
INSERT INTO yy_cn_content
  (id, catid, model_id, title, short_title, author, source, thumb, image,
   keywords, description, content, extend, is_top, is_recommend, is_hot,
   status, views, sort, static_path, create_time, add_time, update_time)
SELECT
  c.id, IFNULL(c.cid,0),
  CASE c.mid WHEN 33 THEN 1 WHEN 34 THEN 2 WHEN 35 THEN 3 WHEN 40 THEN 2 ELSE 1 END AS model_id,
  IFNULL(c.title,''), IFNULL(c.etitle,''), IFNULL(COALESCE(a.author,''),''), '',
  IFNULL(c.pic,''), IFNULL(c.pic,''),
  IFNULL(c.seo_keywords,''), IFNULL(c.seo_desc,''),
  COALESCE(NULLIF(a.content,''), NULLIF(i.content,''), NULLIF(p.content,''), ''),
  NULL, IFNULL(c.istop,0), 0, 0, 1, IFNULL(c.click,0), IFNULL(c.sort,100),
  '', IFNULL(c.create_time,0), IFNULL(c.create_time,0), IFNULL(c.update_time,IFNULL(c.create_time,0))
FROM chty.yunu_content c
LEFT JOIN chty.yunu_diy_article a ON a.conid = c.id
LEFT JOIN chty.yunu_diy_image i    ON i.conid = c.id
LEFT JOIN chty.yunu_diy_productdiy p ON p.conid = c.id;

-- ---------- 3. 广告(banner) -> yy_cn_ad ----------
DELETE FROM yy_cn_ad;
-- PC 轮播 (type=1)
INSERT INTO yy_cn_ad (name, title, width, height, content, status, create_time, update_time)
SELECT 'home_banner_pc', '首页PC轮播', 1920, 600,
  GROUP_CONCAT(CONCAT('<div class="swiper-slide"><a href="', IFNULL(url,'#'),
    '"><img src="', pic, '" alt="', title, '"></a></div>') SEPARATOR ''),
  1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM chty.yunu_banner WHERE type = 1;
-- WAP 轮播 (type=2)
INSERT INTO yy_cn_ad (name, title, width, height, content, status, create_time, update_time)
SELECT 'home_banner_wap', '首页WAP轮播', 750, 400,
  GROUP_CONCAT(CONCAT('<div class="swiper-slide"><a href="', IFNULL(url,'#'),
    '"><img src="', pic, '" alt="', title, '"></a></div>') SEPARATOR ''),
  1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM chty.yunu_banner WHERE type = 2;

-- ---------- 4. 自定义块 yunu_block -> yy_cn_block ----------
DELETE FROM yy_cn_block;
INSERT INTO yy_cn_block (name, title, type, content, image, link, sort, status, create_time, update_time)
SELECT
  IFNULL(title,''), IFNULL(title,''),
  CASE WHEN type = 1 THEN 'html' ELSE 'text' END,
  IFNULL(content,''), '', '', IFNULL(id,100), 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM chty.yunu_block;

-- ---------- 5. 友情链接 yunu_link -> yy_cn_link ----------
DELETE FROM yy_cn_link;
INSERT INTO yy_cn_link (name, url, logo, description, sort, status, create_time, update_time)
SELECT IFNULL(title,''), IFNULL(url,'#'), IFNULL(pic,''), '', IFNULL(sort,100), 1, IFNULL(create_time,0), IFNULL(create_time,0)
FROM chty.yunu_link;

-- ============================================================
-- 迁移完成，以下为校验查询
-- ============================================================
SELECT 'category' t, COUNT(*) c FROM yy_cn_category
UNION ALL SELECT 'content', COUNT(*) FROM yy_cn_content
UNION ALL SELECT 'ad', COUNT(*) FROM yy_cn_ad
UNION ALL SELECT 'block', COUNT(*) FROM yy_cn_block
UNION ALL SELECT 'link', COUNT(*) FROM yy_cn_link;
