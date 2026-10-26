# 易云 CMS（YouYun CMS）

参照 **ESPCMS** 功能架构、**云优 CMS** 后台风格，从零实现的一款现代企业建站系统（ThinkPHP 8.1 多应用实现，v3.25 起由 ThinkPHP 6.1 升级）。

> 说明：用户原计划基于下载的 ESPCMS 源码二次开发，但该目录实际为空、未取得源码，
> 因此本系统为
>
> **全新实现**
>
> —— 在功能面参考 ESPCMS（栏目管理、内容管理、留言板块、
> 静态页面生成、自定义模板、自定义块、广告位、友链、网站设置、后台操作日志等），
> 在后台视觉上参考云优 CMS 的简洁大气风格。



***

## 一、技术栈



* **框架**：ThinkPHP 8.x（topthink/framework 8.1.4）+ think-multi-app 多应用（v3.25 由 TP 6.1.4 无缝升级，官方承诺 6.0/6.1 一键升级）

* **语言**：PHP 8.0+（ThinkPHP 8 要求；已在 PHP 8.2.9 验证）

* **数据库**：MySQL 5.7 / 8.x（utf8mb4）

* **模板**：think-template + 自定义标签库（`{cms:xxx}` + 云优兼容 `{yunu:xxx}`）

* **验证码**：think-captcha

* **富文本编辑器**：UEditor（云优 CMS 同款，后台正文 / 描述 / 单页 / 自定义块；支持全屏、附件改名下载、多图上传）



***

## 二、功能清单

### 后台管理（简洁大气，参考云优 CMS）



* 管理员登录 / 退出（验证码）

* 控制台首页

* **语言版本管理**：后台**自由添加 / 编辑 / 删除语言版本**（如 en、ru），每个语言可单独指定 \*\* 前台模板文件夹名（tpl\_dir）\*\* 与前台 `template/{tpl_dir}` 手动创建的目录联动；新增语言自动建表（克隆默认语言 8 张表 + 复制默认设置）；可设默认语言、启用 / 停用、排序；顶栏语言切换录入

* **栏目管理**：无限级树形、类型（列表 / 单页 / 外链 /**跳转**）、排序、导航 / 显示开关、SEO 设置、**跨语言复制**；列表页**别名 ename 内联编辑**（无需进入编辑页），改别名后按新别名重新静态生成目录

* **内容管理**：多栏目多模型、发布 / 编辑、**富文本编辑器**、扩展字段、批量置顶 / 推荐 / 热门 / 状态 / 删除、浏览自增、**跨语言复制**

* **留言板块**：前台留言、后台列表 / 回复 / 审核 / 删除

* **自定义块**：唯一标识、富文本、按名输出到模板

* **广告位**：唯一标识、按名输出到模板

* **友情链接**：增删改查排序

* **模板管理**：按语言 tpl\_dir + PC/wap 列出目录、在线编辑（防目录穿越）

* **静态页面生成**：按语言（全部语言）生成首页 / 栏目 / 内容 / 全站，目录按 \*\* 栏目别名 (ename)\*\* 生成

* **网站设置**：分组表单（站点基础、SEO、留言、分页条数、**手机版开关**、**自动跳转开关**、上传、后台路径等）

* **回收站（v3.10）**：内容 / 栏目软删除，可批量恢复、彻底删除；栏目删除可勾选「连同子栏目与内容一起删除」（级联软删，可整体恢复）

* **定时发布（v3.10）**：发布内容时可设置定时发布时间，未到时间前台不显示；列表显示「定时」标签

* **站点地图 Sitemap（v3.10）**：动态 `/sitemap.xml` + 全站静态生成自动写入 `public/sitemap.xml`；多语言合并、主语言无前缀；自动生成 `robots.txt`（含 Sitemap 地址）

* **后台数据看板（v3.10）**：控制台首页改版——今日 / 周 / 月 PV、近 7 日趋势、待办（待审内容 / 评论）、最新留言、热门搜索、最近前台访问、最近操作日志

* **评论管理（v3.10）**：前台评论展示 + 后台审核 / 通过 / 隐藏 / 回复 / 批量管理；编辑页顶部显示该内容评论数

* **素材库（v3.10）**：后台上传的图片 / 文件自动收录，网格展示、复制地址、删除

* **搜索日志（v3.10）**：前台搜索词自动落库，后台热门搜索 Top、清空

* **登录安全（v3.10）**：连续失败 5 次锁定 15 分钟；管理员可开启两步验证（6 位数字）

* **数据备份恢复（v3.10）**：后台一键导出 SQL 备份 / 导入恢复（含数据表清单、备份历史）

* **缓存管理（v3.10）**：一键清除运行时缓存（模板编译、配置、路由、日志）

* **收录推送（v3.10）**：向百度 / Google 推送新增 / 更新 URL 收录

* **内容版本历史（v3.10）**：每次编辑自动快照（最多 30 条），可回滚到任意历史版本

* **404 定制页（v3.10）**：前台不存在页面渲染 `template/{语言}/404.html`（无则内置 404 页，后台路径返回简洁提示）

* **REST API（v3.10）**：`/api/content`、`/api/content_detail`、`/api/favorite`、`/api/favorite_count`、`/api/search` 等公开接口（自动路由下划线别名：`/api/content_detail`、`/api/favorite_count` 亦可）


* **管理员管理**：账号 CRUD、修改密码

* 后台操作日志

### 前台（模板化 + 多语言 + 手机版）



* **多语言（自由扩展）**：默认语言（cn）走裸路径 `/`；其他语言带前缀 `/en/`、`/ru/`…；数据表按语言完全隔离（栏目 id 各自从 1 开始）；前台模板目录 `template/{tpl_dir}` 可后台绑定，手机模板 `template/{tpl_dir}/wap/`

* **跨语言复制**：把中文栏目 / 内容一键复制到其他语言栏目，再到对应语言中翻译

* **栏目跳转**：栏目类型可选「跳转」，可设置外部链接或 `@栏目ID`/`@栏目ename` 内部跳转（如一级 "关于我们" 跳转到二级 "公司简介"）

* **手机版**：后台「启用手机版前端」+「自动识别 PC / 手机跳转」开关；手机模板目录 `template/{tpl_dir}/wap/`；支持手动切换电脑版 / 手机版

* **前台搜索**：全站 / 指定栏目，标题 / 正文 / 标题 + 正文，分页（每页条数后台可设，默认 10）

* **列表分页**：后台「列表分页」可设每页条数，默认 10

* 首页、栏目列表页（含侧栏热门与广告位）、内容详情页（上一篇 / 下一篇）、单页（含留言表单 AJAX）

* 完整自定义标签体系（见 `docs/前台标签调用手册.md`）

* 静态页生成按语言 + 别名分开（cn → `public/`，en → `public/en/`，目录用栏目别名）



***

### 云优CMS 无缝迁移（v3.7）
* **云优标签兼容层**：`{yunu:config/nav/list/type/catlist/banner/block/url/tag/form/searchform}` 与云优同属性、同变量（`$list.pic/click/istop/top/create_time/desc` 等字段自动映射），云优前台模板**标签无需重写**
* **模板转换器** `tools/yunu/yunu_tpl_convert.py`：云优 `template/default/index/*.html` → 易云 `template/cn/`（`<yunu:xxx>`→`{yunu:xxx}`、TP3→TP6 语法、`__PUBLIC__`→`/static/yunu`、数字栏目 ID 自动映射）
* **数据库导入脚本** `tools/yunu/yunu_import.php`：云优栏目 / 内容 / 模型 / 自定义块 / 广告 / 友链 → 易云（图片路径自动改写）
* **栏目 ID 映射** `tools/yunu/gen_id_map.php` + `id_map.json`：按 ename（叶子名）匹配两库栏目，供模板转换器替换数字栏目 ID
* **命令行静态生成**：`php think build:static cn`（或后台右上角「静态生成」按钮），无需登录后台即可全站生成

### 首页与源站 1:1 对齐（v3.7.2）
* **修复 `YunuService` 查询链丢失**（`new Model()` 后拆分调用 where/select 导致条件失效）：`catlist` / `banner` 改为静态链承接，实测 `catlist(61,'son')` 正确返回 5 个二级、`banner('1')` 正确返回 PC 组 3 张
* **`{yunu:list}` cid 递归包含全部后代栏目**：如 `cid="61"`（产品中心）自动取到三级栏目内容，首页产品列表 8 条与源站完全一致
* **`{yunu:catlist}` 排序固定 `sort asc, id asc`**（对齐云优：其 getCatlist 内部固定 sort asc，模板 orderby 为死参数），企业优势 7 个二级栏目顺序与源站一致
* 已验证首页全部区块（banner / 关于我们 / 新闻中心 / 产品菜单 / 产品列表 / 企业优势 / 友链）与 `http://chty.com/` 数据一致；列表页 / 详情页正常

### 会员 / 商城 / 客服（v3.12）
* **会员注册审核**：后台「会员设置」可开启 / 关闭注册审核；开启后新会员注册为「待审核」，后台「会员管理」审核通过才能登录；支持查看注册 IP、审核备注；未审核登录提示「账号待审核」
* **注册验证码**：后台可开关注册页验证码（think-captcha），防止恶意批量注册；登录页验证码默认开启
* **商城系统**：后台「商品价格」为内容设置价格 / 原价 / 库存 / 上架状态；前台会员可将商品加入购物车（数量增减、单选删除）、结算下单（收货人 / 电话 / 地址）、支付（模拟支付 / 线下转账，后台订单管理确认收款）；订单详情含商品快照，支付后自动扣库存、累加销量
* **客服系统**：前台会员「在线客服」发起会话、发送消息、4 秒轮询刷新；后台「客服会话」接待、实时回复（4 秒轮询）、关闭会话；未读消息计数；会话列表按最后消息时间倒序
* **会员中心**：前台 `/member/center` 聚合导航（我的订单 / 购物车 / 在线客服 / 退出登录等），样式独立不依赖站点模板
* **数据表**：`yy_member`（+audit_remark/reg_ip）、`yy_shop`、`yy_cart`、`yy_order`、`yy_order_product`、`yy_service_session`、`yy_service_msg`；配置键 `member.reg_open / reg_captcha / audit`、`shop.pay_test / pay_offline`、`service.open`（`database/upgrade_v312.sql`）

### 站群系统（v3.13）
* **站群开关**：网站设置 → 站群设置 → `site.multi`（默认关闭=单站点模式；开启=站群模式，侧边栏出现「站群管理」）
* **站群管理**：站点 = 域名入口 + 默认语言 + 语言白名单。支持添加/编辑/删除、排序、启停、备注
* **域名匹配**：精确（`newcms.com`、`newcms.com:8080`）或通配（`*.example.com` 匹配任意子域）
* **前台规则**：命中站点 → 站点默认语言走裸路径，其他白名单语言带前缀（`/en/`）；URL 首段为白名单外语言时自动回落站点默认语言
* **数据边界**：同一语言数据多站共享（栏目/内容/留言/块/广告按语言隔离），站点差异通过「域名 + 语言白名单 + 默认语言」实现；如需站点内容完全隔离，为站点创建独立语言版本即可
* **数据表**：`yy_site`（id/name/domain/default_lang/langs/status/sort/remark/create_time），配置键 `site.multi`（`database/upgrade_v313.sql`，幂等）

### 演示数据 + 现代模板改造（v3.14）
* **演示数据**：`database/upgrade_v314_demo.sql`（幂等）造数——内容 200-215（产品 200-209 分属栏目 72-76 + 文章 210/211）、`yy_shop` 价格 10 条（¥36~¥18800）、评论 100-112（含官方回复）、收藏 100-103、购物车 100-101、订单 100 待付 136 元 / 101 已付 680 元（offline 通道）；时间列使用纯时间戳表达式（`yy_*_content.create_time` 为 int 列，写 `FROM_UNIXTIME()` 会 ERROR 1264）
* **现代模板（template/cn/）**：新增 `css/modern.css`（yy- 前缀类、CSS 变量 `--yy-primary:#2563eb / --yy-accent:#f97316`、蓝橙主色、14px 圆角卡片、grid 网格、响应式）；重写 header.html（顶部信息条 + sticky 导航 + 搜索 + 购物车徽标 + 登录态用户菜单）、footer.html、index_index.html（hero + 功能六卡片 + 产品价格网格 + 新闻 + 案例 + 留言入口 + 友链）、list.html（现代双栏 + `{$list|raw}` 翻页）及 list_article/list_page/list_contact/list_message 副本、list_product.html（yy-grid + `{$shop_map[$vo.id]['price']}` 价格 + 置顶/热销标签）及 list_pic 副本、show_product.html（双栏商品区/主图缩略图切换/数量器/加购 POST /shop/cart_add/立即购买跳 /shop/checkout/收藏 POST /api/favorite/评论列表含官方回复 + 表单 + 验证码图）、show.html（single 兼容公司简介/联系我们/在线留言，非 single 才显示 meta 与评论）及 show_article/show_pic 副本、search.html（搜索条 + 栏目下拉 + 标题/正文 tabs）、wap/ 移动端同步
* **前台功能可视化**：会员登录态（header 用户菜单/购物车徽标）、商品价格/原价/库存/已售、加入购物车、立即购买、收藏、评论（提交/验证码/官方回复）、留言板（填写/查询码/公开留言列表）、搜索（标题/正文/指定栏目）
* **后台登录验证码开关**：网站设置 → 全局设置 → `admin.captcha`（默认开启）；关闭后登录免验证码（`app/admin/controller/Login.php`）
* **静态生成修复**：`StaticPage::category()` 列表分支注入与 Lists 控制器一致的 `$list`（当前页数据）/`shop_map`；`StaticPage::content()` 注入 `price/original_price/stock/sales/shop_status` 与 `fav_status`；详情模板评论变量 `isset` 保护（PHP8 下 `{:count($comments)}` 未注入会报错）
* **模板语法陷阱**：`{$shop_map[$vo.id].price}` 会被 TP 编译为 `$shop_map[$vo['id']].price`（PHP 解析为字符串连接常量 → `Undefined constant "price"`），必须写成 `{$shop_map[$vo.id]['price']}`
* **静态页文件**：主语言首页 `public/index.html`，栏目/内容页 `public/{主语言文件夹}/`（如 cn/），其他语言 `public/{lang}/`；nginx 伪静态 `public/nginx.htaccess` 由 `sync_pretty_firewall()` 自动生成（rewrite 单段栏目到主语言 index.html + try_files 兜底动态）

### v3.25 ThinkPHP 8 升级（2026-10）

* **框架升级**：ThinkPHP 6.1.4 → **8.1.4**（think-orm 4.0.51 / think-template 3.0.2 / think-view 2.0.5 / think-multi-app 1.1.1 / think-captcha 3.0.11 / think-trace 1.6），走官方 6.0/6.1 无缝升级路径
* **兼容修复**：多语言表名机制适配 ORM4——`app/common/model/LangModel.php` 改为覆盖 `getOptions()` 注入 `['name' => lang_code().'_'.$this->name]`（ORM4 表名由 weakMap options 决定、构造函数 initializeData 阶段即查表，属性赋值无效）；构造尾保留 ORM3 兜底并防重复前缀（避免 cn_cn_category）
* **依赖调整**：移除 `topthink/think-filesystem`（全项目代码零引用；其 v2.x 依赖 flysystem 存在安全公告 PKSA-w9tt-7782-78jx / PKSA-pwh8-d4fr-nywn 导致 composer 解析失败）
* **环境**：PHP 8.2.9 + 启用 `zip` 扩展（composer 解压必需）
* **全站回归**：前台核心页（首页/新闻/产品/详情/搜索/客服/会员登录/留言/多语言/站群）、后台 37 个菜单页面、内容增删、留言提交、会员登录验证码拦截、三语言静态页重建（cn 60 / en 9 / shenyang 62 页）全部通过，无错误页、无错位
* **回滚入口**：升级前备份于 `D:\phpstudy_pro\WWW\_tp6_backup\`（TP6 的 composer.json/lock + vendor）

### v3.26 全站可升级可完善体检落地（2026-10）

**体检结论**（先体检后实施）：composer audit 无安全通告；依赖层除 think-trace 外已全部最新；无 eval/system 危险函数；原生 SQL 仅存在于备份/建表/分表白名单场景；上传有大小+扩展名双重白名单；核心表索引齐全。据此落地 8 项：

| 项 | 内容 |
|---|---|
| **P0 商城 title 语义** | 商品商城/购物车/确认订单三页 title 由「- 会员中心」改为「- 网站名」；`Shop::page()` 注入 `site_name`（`get_config('site.name')`） |
| **P1a N+1 批量优化** | `Shop.php` 三处循环内逐条查库改 whereIn 批量 + 内存 Map：购物车列表（内容+商品）、售后列表（订单号）、订单列表（订单商品）。**保留** `confirm()` 事务内行锁逐条查询（SKU 原子扣减防超卖，不能批量）；`Member::center()` 收藏已是批量写法未动 |
| **P1b 索引补充** | `yy_cart` 加 `idx_member_lang(member_id, lang)`；`yy_service_msg` 加 `idx_session_id(session_id, id)` |
| **P2a think-trace 升级** | 1.6 → **2.0**（composer require topthink/think-trace:^2.0），首页/后台实测 trace 渲染正常 |
| **P2b 后台 CSRF 防护** | 新增 `app/common/middleware/CsrfCheck.php`（注册于 SessionInit/LangRouter 之后、AdminAuth 之前）：仅拦截后台路径的 POST/PUT/DELETE，带 Referer/Origin 时必须同源，无来源信息放行（兼容支付回调/采集/API）。三场景实测：同源登录 200、跨站 Referer 被拒「请求来源不合法」、无来源放行 |
| **P2c TP8 渐进式强类型化** | 公共方法统一返回类型：`AdminBase::ok()/fail()` → `\think\response\Json`；Shop/Member/Service 的 `back()` → `Json`、`page()` → **`string`**（TP8 的 `View::fetch()` 返回 string 而非 Response，初版误标 `\think\response\View` 致 500，已修正）；CsrfCheck::handle → `Response` |
| **P2d REST API 扩展** | `Api.php` 新增 4 个只读端点：`/api/category_list`（栏目列表/导航）、`/api/shop_list`（上架商品含价格/库存/销量/是否有规格）、`/api/member_info`（登录会员脱敏资料）、`/api/favorite_list`（登录会员收藏）；兼容下划线别名（category_list/shop_list/member_info/favorite_list） |
| **P2e 上传控件 SVG 净化** | 体检确认现状已安全（大小+扩展名白名单、重命名存储），维持现状 |

**回归验证**：清 `runtime/cache`+`runtime/temp` 后实测——前台首页/商城/产品详情/留言/API 全部 200；后台登录（CSRF 同源）、控制台/内容/商品/留言/会员/菜单/钱包/采集 8 个关键菜单 200；后台内容提交 POST 未被 CSRF 误伤。三副本（运行版 + F:\易云cms + F:\豆包下载\CMS）app/vendor/composer 已同步；docs 已更新。

### v3.26.1 后台消息通知优化：仅未读时提醒（2026-10）

**问题**：v3.24 通知铃铛的闪烁定时器一旦开启永不关闭（`blinkOn` 无停止分支），且打开通知中心不标记已读，导致红点与标题闪烁「一直存在一直闪」。

**修复**：
- **前端 `app/admin/view/layout.html`**：重构铃铛逻辑——`stopBlink()` 在无未读（total=0）时 `clearInterval` + 恢复原标题；`startBlink()` 仅在未闪时开一次定时器（标题在 `(N) ● 标题` 与 `标题` 间交替）；total=1 时红点静态亮、≥2 才脉冲动画；点击铃铛先本地清红点/停闪烁，再调 `notifyRead` 标已读，最后跳转通知中心。
- **后端 `app/admin/controller/Service.php` 新增 `notifyRead()`**（POST）：打开通知中心即已读——①全部未读会员客服消息 `is_read=1`；②未接待新会话（admin_id=0）归属当前管理员（视为已查看，不自动接单）；③未读留言标已读（仅当该管理员开启留言通知 `notify_guestbook=1`）。

**实测**：构造未读（客服消息 1 + 新会话 1 + 留言 12）→ 铃铛 `on pulse`、标题闪烁；点击铃铛 → 红点消失、标题恢复、iframe 进入通知中心；`notifyCount` 返回 `total:0`；数据库未读全部归零。测试数据已清理。

## 三、安装部署

### 1. 环境要求



* PHP ≥ 8.0（推荐 8.1+），需启用扩展：`pdo_mysql`、`mysqli`、`gd`（验证码）、`mbstring`、`curl`、`openssl`、`fileinfo`、`zip`

* MySQL 5.7 或 8.x

* Composer 2.x

### 2. 安装依赖



```
composer install --no-dev

\# 若因框架安全公告被拦截（Packagist），可加参数：

composer install --no-security-blocking
```

### 3. 配置环境

复制 `.env.example` 为 `.env`，填写数据库连接：



```
\[DATABASE]

TYPE = mysql

HOSTNAME = 127.0.0.1

DATABASE = youyun\_cms

USERNAME = root

PASSWORD = 你的密码

HOSTPORT = 3306

CHARSET = utf8mb4

PREFIX = yy\_
```

### 4. 导入数据库

在 MySQL 中创建数据库 `youyun_cms`（utf8mb4），导入：



```
database/install.sql
```

默认管理员：**admin / admin888**（首次登录后请立即修改）。

> install.sql 为多语言版：共享表 5 张 + cn 语言表 8 张 + en 语言表 8 张。
> 后台「系统设置 → 语言版本」可
>
> **自由添加更多语言**
>
> （如 ru），系统自动为其克隆 8 张数据表并复制默认设置，
> 前台需手动创建对应模板文件夹（如 
>
> `template/ru/`
>
> 、
>
> `template/ru/wap/`
>
> ）并绑定到该语言的「模板文件夹名」。

### 5. 目录权限

确保 `runtime/`、`public/upload/` 目录可写。

### 6. 启动



* **开发**：`php think run -H 0.0.0.0 -p 8000`，访问 `http://127.0.0.1:8000`

* **后台**：`http://127.0.0.1:8000/admin`（路径可由设置 `base.admin_path` 修改）

* **其他语言版**：`http://127.0.0.1:8000/en`、`http://127.0.0.1:8000/ru`…（语言前缀由后台启用的语言动态生效）

* **生产**：站点根目录指向 `public/`；Apache 已配 `.htaccess`，Nginx 需配置伪静态（将非文件 / 目录请求转发到 `index.php`）。若启用静态页生成，Nginx 需确保静态 `.html` 文件优先返回。



***

## 四、验证情况（交付边界）



* ✅ **PHP 语法检查**：全部 PHP 文件 `php -l` 通过

* ✅ **多语言核心逻辑**：LangModel 表名按语言前缀生成已验证（`yy_cn_content` / `yy_en_content`）；语言注入经 `withMiddleware` 通道实现；动态 app\_map（`LangAppService`）按后台启用语言生成

* ✅ **运行时验证**（PHP 8.2.34 内置服务器）：


  * 登录页 `GET /admin/login/index` → 200 正常渲染

  * 未登录访问后台 → 302 重定向到登录页（Auth 中间件生效）

  * 前台模板平铺解析（前导 `/` 模板名）、header 变量守卫（`$seo_title` 等 `isset` 包裹）已验证：前台首页可正常进入模板渲染阶段

  * 前台 `/search/index`、`/en/lists/index` 等路由解析正常，仅因**本机未安装 MySQL** 在数据库连接处返回 500（预期）

* ⚠️ **端到端数据库流程**（登录校验、栏目 / 内容 / 留言 CRUD、动态语言增删、跨语言复制、栏目跳转、别名内联编辑、多语言静态页生成、前台取数与搜索）**需在真实 MySQL 环境下验证**。本机无 MySQL 服务，故未能覆盖该部分。

> 部署到含 MySQL 的服务器后，请重点验证：登录 → 新增一个语言（如 ru，确认自动建表）→ 切换录入 → 中文栏目 / 内容复制到其他语言并翻译 → 栏目跳转（@id/@ename / 外链）→ 列表页内联改别名 → 按语言 + 别名静态生成 → 前台搜索 → 手机版跳转 → 前台各页面与标签展示。



***

## 五、文档



| 文档                  | 说明                                          |
| ------------------- | ------------------------------------------- |
| `docs/目录结构与文件说明.md` | 逐目录、逐文件作用说明，二次开发指南（含多语言 / 手机版 / 搜索实现要点）     |
| `docs/前台标签调用手册.md`  | 完整前台 `{cms:xxx}` 标签调用手册（含多语言 URL / 搜索 / 分页） |



***

## 六、目录结构（概览）



```
├── app

│   ├── admin            # 后台应用（controller + view，含 Language 语言版本管理）

│   ├── index            # 前台应用（controller：含 Search 搜索、FrontBase 模板目录选择）

│   ├── common           # 公共层：model / service / taglib / middleware

│   ├── common.php       # 全局公共函数（多语言/配置/URL/设备/跳转解析）

│   ├── service.php      # 服务注册（动态语言 app\_map）

│   ├── BaseController.php

│   └── middleware.php   # 全局中间件入口

├── config               # 应用配置（app.php 的 app\_map 由服务动态填充）

├── database/install.sql # 建库脚本（多语言版，yy\_lang 含 tpl\_dir 模板文件夹绑定）

├── docs                 # 文档

├── public               # Web 根目录（index.php / 静态资源 / upload / 静态页）

├── runtime              # 运行时（可写）

├── template

│   ├── cn               # 中文 PC 模板（平铺在目录根，模板文件夹名可后台绑定）

│   ├── cn/wap           # 中文手机版模板

│   ├── en               # 英文 PC 模板

│   └── en/wap           # 英文手机版模板

│   （新增语言：手动创建 template/ru、template/ru/wap 并在后台绑定）

├── composer.json / composer.lock

├── think                # 命令行入口

└── .env.example
```

详细说明见 `docs/目录结构与文件说明.md`。

---

## v3.10.2 采集器 + 大数据量分表（2026-10-10）

> 应需求新增两大生产级功能，并确认 REST API 与插件钩子已在 v3.10 就绪。

**采集器（后台：扩展功能 → 采集器）**

| 能力 | 说明 |
| --- | --- |
| 规则管理 | 规则名称 / 目标语言 / 目标栏目 / 目标站编码 / 状态 |
| 列表抓取 | 列表页地址支持 `{page}` 翻页占位；列表区域「起止标记」截取；链接正则提取（默认 `<a href>`，自定义时第 1 捕获组为 URL） |
| 详情提取 | 标题 / 正文「起止标记」模式；摘要自动截取；过滤词（标题含则跳过） |
| 入库 | 标题自动去重（目标语言全表）；正文图片可选下载到本地 `public/upload/image/`，第一张作缩略图 |
| 日志 | 每次执行记录抓取 / 新增 / 跳过 / 失败数 |
| 权限 | 受限角色仅可向自己可管理栏目采集 |

**大数据量分表（后台：系统设置 → 大数据分表）**

| 能力 | 说明 |
| --- | --- |
| 分表规则 | 内容表按年份归档：主表 `yy_{lang}_content` 只保留最近 N 年（后台可设，默认 2），更早整年数据迁入 `yy_{lang}_content_{YYYY}` |
| 详情路由 | 前台详情页 URL 不变：主表未命中自动按年份倒序查归档表（列表 / 搜索仍查主表=当前数据） |
| 合并回主表 | 归档表一键回迁（主键冲突自动跳过） |
| 数据库 | 无需预建表（执行时 CREATE TABLE LIKE 自动创建） |

**确认已存在（无需新建）**：REST API（`/api/content`、`/api/content_detail`、`/api/favorite`、`/api/favorite_count`、`/api/search`）；插件钩子（`app\common\Hook.php`：`Hook::on` 注册 / `Hook::listen` 触发，插件放 `addons/插件名/hooks.php`，示例 `addons/example/`）。

**数据库**：`upgrade_v310.sql` 追加全局表 `yy_collect_rule` / `yy_collect_log`；权威 `install.sql` 已合并（43 表）。

***

## v3.10.1 前台会员中心（2026-10-10）

> 新增最小可用的前台会员中心闭环：注册 / 登录 / 退出 / 我的中心。

| 入口 | URL | 说明 |
| --- | --- | --- |
| 注册 | `/member/register` | 用户名（3-30 位字母数字下划线）+ 昵称 + 邮箱 + 密码；注册成功自动登录 |
| 登录 | `/member/login` | 账号 + 密码 + 验证码（think-captcha）；连续错误 5 次锁定 15 分钟 |
| 我的中心 | `/member/center` | 我的资料（可改昵称 / 邮箱 / 手机 / 密码）+ 我的收藏 + 我的留言（按邮箱匹配） |
| 退出 | `/member/logout` | 清除登录态回首页 |
| 验证码 | `/member/captcha` | 验证码图片（登录页引用，点击刷新） |

- **收藏联动**：`/api/favorite` 已支持「已登录会员用会员ID、未登录用匿名标识」双模式；登录后收藏的内容在「我的中心」直接展示。
- **数据表**：`yy_member`（全局会员，不分语言）；收藏 `yy_{lang}_favorite`；留言 `yy_{lang}_message` 按邮箱关联。
- **视图**：内置视图 `app/index/view/member/{login,register,center}.html`，独立样式、自适应手机，不依赖云优模板 header/footer，可自行改样式。
- **入口接入模板**：在任意模板加 `<a href="/member/center">会员中心</a>` 即可；或加 `{cms:member}` 占位（v3.11）。

***

## v3.10 对标主流 CMS 功能落地（2026-10-10）

> 本轮将「易云 CMS 与主流 CMS 对比」提出的 24 项待完善功能分四组落地，并对齐三份副本与五份文档。

**P0 基础四件**

| 功能 | 实现说明 |
| --- | --- |
| 回收站 | 内容 / 栏目软删除（`delete_time`），列表可切换「回收站」视图；批量恢复、彻底删除；栏目支持勾选级联删除并整体恢复（自动先恢复父级） |
| 定时发布 | `yy_*_content.publish_time`（0=立即），编辑页 datetime-local 选择，前台一律 `published()` 过滤，列表显示定时标签；静态生成对未到期内容不生成 |
| Sitemap | `app\common\service\SitemapService` 生成多语言合并 XML；动态 `/sitemap.xml` + 全站静态生成自动写入；`write_robots()` 生成 `robots.txt` |
| 后台数据看板 | 控制台首页：今日 / 周 / 月 PV、近 7 日趋势（CSS 条形）、待办、最新留言、热门搜索、最近前台访问、最近日志 |

**P1 安全 / SEO / 运营 / 体验**

| 功能 | 实现说明 |
| --- | --- |
| 登录失败锁定 + 两步验证 | `yy_admin.login_fail / lock_time / twofa_secret`；连续失败 5 次锁 15 分钟；登录后若已启用两步验证进入 `login/twofa` 输入 6 位 PIN |
| 评论管理 | `yy_*_comment` 表 + 后台审核 / 通过 / 隐藏 / 回复 / 批量；内容编辑页顶部显示评论数（前台展示标签为 v3.11 预留） |
| 素材库 | `yy_*_asset` 表 + 后台网格管理；后台上传自动收录（Upload 控制器调用 `Asset::add`） |
| 搜索日志 | `yy_*_search_log` 表；前台 Search 自动 `record()`；后台热门搜索 Top20 + 清空 |
| robots.txt / 404 定制 | `write_robots()` 生成；`app\ExceptionHandle` 渲染 `template/{语言}/404.html` 或内置 404（后台路径返回简洁提示） |
| 收录推送 | 后台「收录推送」：百度 / Google 手动推送 URL 列表（`pings` 接口，可自测返回码） |
| 一键备份恢复 | 后台「数据备份」：mysqldump 全库导出为 `.sql` 下载、历史备份清单、导入恢复（执行前自动再备份） |
| 缓存管理 | 后台「缓存管理」：清模板编译缓存 / 配置缓存 / 路由缓存 / 日志 |
| 内容版本历史 | `yy_*_content_log` 快照（每内容 30 条），编辑页展开查看 + 回滚 |
| 静态页定时过滤 | StaticPage 生成栏目 / 详情 / 上下条均过滤未到定时发布的内容 |
| 登录无操作超时 | 站点设置 `session_timeout`（30 分钟 / 1 小时 / 6 小时 / 自定义，默认 1 小时） |
| 引导页开关 | 站点设置 `base.splash`（默认关闭），开启后前台首页渲染 `template/{语言}/index_index.html` |

**P2 平台化**

| 功能 | 实现说明 |
| --- | --- |
| REST API | `/api/content`（列表）、`/api/content_detail`（详情）、`/api/favorite`（匿名收藏/取消）、`/api/favorite_count`、`/api/search`；路由文件规则 + 自动路由下划线别名双通道（本机环境 route 文件不加载，故提供下划线别名方法） |
| 版本历史 | 见上（内容快照与回滚） |

**数据库升级**：`database/upgrade_v310.sql`（新增 10 张表：`yy_{cn,en}_comment / favorite / asset / content_log / search_log`；`content` 加 `publish_time / delete_time`；`category` 加 `delete_time`；`admin` 加 `login_fail / lock_time / twofa_secret`；`member` 加 `last_login`）。权威 `install.sql` 已同步合并以上全部结构（新装无需升级脚本）。

**已知边界（如实）**：评论前台展示与会员收藏按钮前端已在 v3.14 随 `show_product.html` 落地（`{cms:comment}` 独立标签仍为预留，当前用模板内 foreach）；文件完整性扫描（`Scan` 控制器）本轮未落地，菜单未加入口。

***

## v3.7.4 内页四项修复（2026-10-09）

1. **单页侧边栏对齐源站**：`show.html` 侧边栏由「全局栏目导航+热门资讯」改为 `public_left`（当前顶级栏目名 + 其子级列表 + 联系我们），与云优单页完全一致（公司简介页 = 公司简介/企业文化 + 联系信息）。
2. **单页去掉文章详情元素**：上一页/下一页（`{cms:prevnext}`）加 `is_single` 条件，单页不再显示「上一条/下一条」。
3. **顶部导航 hover 二级下拉**：`YunuService::nav()` 补 `child` 兼容键（与 catlist 一致）；`yunu.js` 解除 `width>991` 限制，PC/移动端 hover 均显示二级导航，点击箭头亦可展开。
4. **列表分页静态化**：
   - 模板 `{$page}` 全部改 `{$page|raw}`（不再被 HTML 转义）；
   - `YunuService::contentList()` 静态构建下读取 `YY_STATIC_BUILD` 全局（页号 + 静态分页路径），分页链接为 `/栏目路径/page/2.html` 形式；
   - `StaticPage::category()` 按栏目 pagesize 计算总页数，逐页生成 `index.html` + `page/N.html`（此前只生成第 1 页）。

修改文件：`app/common/service/YunuService.php`、`app/common/service/StaticPage.php`、`template/cn/show.html`、`template/cn/list_article.html`、`template/cn/list_pic.html`、`template/cn/list_product.html`、`public/static/yunu/js/yunu.js`。
静态重建：`php think build:static cn`（分页文件 page/N.html 自动生成）。

### v3.7.4 补充：SEO 与底部二维码同步（2026-10-09）
- **根因**：云优模板用下划线键（`site_title`/`seo_title`），易云设置表用点键（`site.name`/`seo.title`），`YunuService::setting()` 增加**别名映射**（site_title→site.name、seo_title→seo.title 等 11 组），首页/列表/单页 SEO 全部接通。
- **设置表已同步源站**：site.name=沈阳辰宏体育设施有限公司、seo.title/keywords/description 完整复制源站值、site.copyright 含备案链接。
- **底部二维码**：`YunuService::block()` 对 `type=image` 且 content 为空的自定义块自动输出 `<img src="image">`（ewm/sjewm 已指向源站二维码图片），前台 `{yunu:block name="ewm" /}` 直接显示二维码；不影响纯文本块。
- 重建后验证：首页 title/keywords/description、列表页 `栏目名-公司名`、单页标题、底部双二维码、版权备案全部正确。

### v3.8.9（模板资源 __PUBLIC__ 机制）
- **模板资源目录**：前台模板 css/js/图片/字体从 `public/static/yunu/` 移至**模板同级目录** `template/{语言}/`（如 `template/cn/css`、`template/cn/js`、`template/cn/images`、`template/cn/fonts`）。
- **`__PUBLIC__` 重定义**：`config/view.php` 中 `__PUBLIC__` = `/assets/index?path=`（新增前台 `Assets` 控制器动态输出模板资源，读取 `template/{当前语言}/`，多语言缺失回退主语言，白名单扩展名 + 防目录穿越）。
- 模板引用统一改为 `__PUBLIC__/css/x.css`、`__PUBLIC__/js/x.js`、`__PUBLIC__/images/x.png`；已批量替换 16 个模板 128 处 `/static/yunu/` 引用，静态生成页同步走 `/assets` 入口。
- 云优模板转换器同步更新：`__PUBLIC__` 保留不转换，云优 statics 资源复制到 `template/{语言}/`。
- 清理：`public/static/yunu` 已删除（运行版 + 两份 F 盘副本），旧静态残留（solution*、contact 等跳转栏目旧页）已清除。

### v3.8.8（引导页开关）
- **网站设置 → 全局设置** 新增「引导页开关」（`base.splash`，默认关闭）。
- 开启后：站点根 URL 显示引导页（模板 `index_index.html`）；引导页内「进入网站」链接用 `{yunu:url name="homepage" /}`（主语言 `/home`，其他语言 `/{lang}/home.html`），进入正常首页（模板 `index.html`，新增前台 `Home` 控制器）。
- 静态生成联动：开启引导页时同时生成 `index.html`（引导页）+ `home.html`（正常首页）；关闭时仅 `index.html`（正常首页）。
- 三副本代码（Setting/Index/Home/StaticPage/Yunu/common/route）+ install.sql（`base.splash` 默认关闭）+ 三份文档同步更新。

### v3.8.7（2026-10-09）F 盘安装发布包（本次交付）
- 新建 `F:\易云cms` 完整安装发布包：系统文件 + 清洗后数据库（`database/install.sql`，60KB，31 张表）+ 云优风格前台模板 28 个 + 四份文档。
- 数据库清洗规格：栏目 5 个演示栏目（公司简介/新闻中心/产品中心/联系我们/在线留言，ID 从 1 开始）；内容 4 篇演示（新闻 2 + 产品 2）；留言板保留默认 1 个；自定义块保留基础块 12 个（tel/mobile/email/address/ewm 等）；管理员仅保留超级管理员；操作日志/浏览统计/广告/友链清空；内容模型仅文章模型（id=1）；栏目模型仅默认模型（id=1）；网站标题/关键词/SEO 全部重置为「易云CMS」。
- 前台模板：`template/cn` 已全部替换为用户提交的云优模板（E:\最新后台\template\default\index），15 个模板文件已转换为易云调用规则（yunu: 标签统一、include 引用修正 location → public_location）；css/js/img/fonts/source 资源随模板目录存放，并同步复制至 `public/static/yunu` 供 `/static/yunu/` 引用；系统必需补充 4 个公共模板（header.html/footer.html 因用户源文件为 0 字节空文件、show.html 单页详情、public_left.html 侧边栏）使用当前运行版（yunu 调用规则）补齐；栏目模板命名已对齐（联系我们 → list_lianxi、在线留言 → guestbook_index）。
- 全新部署模拟测试通过：建库 → 导入 install.sql → `php think build:static cn` 生成 10 个静态页面（主语言 index.html 生成于根目录，栏目静态文件生成于主语言文件夹 `cn/` 下，浏览器链接不体现 cn 前缀），首页导航/SEO/图片引用渲染正常。
- 安装方法：phpstudy 新建数据库（utf8mb4）→ 导入 `database/install.sql` → 修改 `.env` 数据库连接 → 站点根目录指向 `public` → 后台 `/admin` 登录 → 「静态生成」生成全站静态页。

### v3.8.6 全新部署实战支持（2026-10-09）
- **场景**：用户在新站点全新部署易云（install.sql 初始化）+ 云优包（data.sql + template 文件夹）一键迁移。
- **yunu_import.php 新增参数**：
  - `--yunu-sql=路径`：直接传云优 `data.sql`，脚本自动建临时库（yunu_tmp_xxx）并 mysql CLI 导入，避免手工 SQL（杜绝 #1136 等）；
  - `--yunu-dir=目录`：云优包目录，图片源自动定位 `{dir}/uploads`（兼容 upload / statics/uploads / uploads/image）；
  - `--mysql-bin=路径`：指定 mysql.exe（Windows phpstudy 常见路径）。
- **确认易云初始库来源**：`database/install.sql`（29 表，含默认管理员/设置/中英文基础表）——全新部署导入此文件即可。
- **tools/yunu/README.md 新增「二·实战」节**：全新站点四步（建库+install.sql+env → yunu_import --yunu-sql/--yunu-dir → 模板转换 → 清缓存+静态生成+验证）。
- **验证**：php -l 通过。

### v3.8.5 云优迁移导入三件套升级（2026-10-09）
- **目标**：后续用户提供**新的云优模板 + 新的云优数据库**，一键导入易云 CMS 且避免全部历史踩坑。
- **yunu_import.php 增强**：
  - 新增 `--with-message`（云优留言→易云默认留言板，自动生成查询码）、`--no-files`（跳过图片文件复制）；
  - 富文本实体规范化 `norm_html()`（`&lt;p&gt;`→真实标签，双解码），应用于栏目 desc/content、内容 description/content、块 content、留言 content/reply；
  - 图片集**双写**统一口径：image 列 `***` 分隔（后台回显）+ extend.piclist 数组（前台云优键）；
  - 栏目/内容/留言逐条 `ytry()` 错误收集，单条失败不中断，末尾汇总错误清单；
  - 末尾自动生成 `id_map.json`（云优栏目ID→易云栏目ID），模板转换器直接读取；
  - 头部注释内置 7 条避错说明（PDO 无 #1136、实体解码、图集双写、@跳转映射、字段落列、错误收集、自动映射）。
- **yunu_tpl_convert.py 增强**：
  - 富文本输出自动补 `|raw`（`{$type.desc}`/`{$content.desc}`/`{$category.content}` 等无管道输出；有 `|str2sub` 截断的不动）——避免 p 标签文本显示；
  - 资源路径 `/uploads/image/` → `/upload/image/`。
- **新增 tools/yunu/README.md**：完整导入指南（数据库/模板/命名规则/历史踩坑→避错对照表 16 条/导入后验证清单/FAQ）。
- **验证**：php -l 通过；转换器补 |raw 正则 8 组单测全过（含 str2sub/sitelink 管道豁免）。

### v3.8.4 富文本输出全面补 |raw（2026-10-09）
- **问题**：首页"关于我们"text-con 显示 `&lt;p&gt;` 实体——`{$type.desc}` 未加 `|raw`，ThinkPHP 模板默认转义富文本 HTML。
- **修复**：全站扫描补 6 处富文本输出 `|raw`：
  - index_index.html `{$type.desc}`（首页关于我们）、list_contact.html / list_page.html `{$category.content}`（单页栏目内容）、show_article.html / show_pic.html / show_product.html `{$content.desc}`（文章/图片/产品描述）。
- **验证**：首页 text-con 渲染真实 `<p>建筑工程施工...</p>`；实体 `&lt;p&gt;` 残留 0；静态重建 56 页。

### v3.8.3 图片集后台回显与前台口径统一（2026-10-09）
- **问题**：产品图集数据存于 `extend.piclist`（数组），前台轮播正常；后台编辑页读取 `image` 列（空）→ 图片集显示为空；且编辑保存会覆盖图集为空。
- **修复**：
  - 后台编辑回显（Content::edit GET）：image 列 `***`/`,` 分隔解析，为空时兼容 `extend.piclist`；
  - 编辑页 hidden 值改为 `{:implode('***', $imageList)}`（不再读 image 列）；
  - collectPost 图片集**双写**：保存时同时写入 image 列（`***` 分隔）与 extend.piclist（数组），前后台口径一致；
  - YunuService::mapContent：piclist 兼容 extend.piclist，为空时回退 image 列。
- **数据迁移**：8 条产品（catid=86）extend.piclist → image 列已一次性迁移（PHP 脚本）。
- **验证**：106 轮播 12 图正常；后台 imageList 兼容逻辑单测通过；php -l 全部通过。

### v3.8.2 前台搜索功能修复（2026-10-09）
- **问题一**：公共头部搜索表单 `method="post"`，但搜索控制器读 GET 参数 → 提交后关键词丢失，搜索页无结果。
- **修复**：`header.html` 搜索表单改 `method="get"`（search.html 已是 GET）。
- **问题二**：`{yunu:list keyword="$keyword"}` 中裸变量 `$keyword` 不被标签库识别，被当作字面量字符串"$keyword"去模糊搜索 → 无结果。`expr()` 只识别 `{$var}` 模板变量语法。
- **修复**：`Yunu.php expr()` 增加裸 `$` 开头变量的识别（`$value[0] === '$'` → varExpr 解析为 PHP 变量），云优模板 `keyword="$keyword"` 直接可用。
- **验证**：GET /search?kw=浑河西峡谷 → 5 条结果（标题匹配）；kw=传感器/体育 → 均有列表；首页静态页搜索表单已为 GET。

### v3.8.11 内容发布时间列 publish_time → create_time（2026-10-10）
- **内容表列改名**：`yy_cn_content` / `yy_en_content` 的 `publish_time` → **`create_time`**（发布时间），原 `create_time`（建立时间）→ **`add_time`**；索引 `idx_publish` 保留指向新 `create_time`
- **后台表单**：发布时间 name=create_time_dt（JS 提交 `fd.set('create_time', ...)`），建立时间 name=add_time_dt（JS 提交 `fd.set('add_time', ...)`）；collectPost 映射 update_time→create_time（云优表单兼容）
- **行内编辑白名单**：`['title','views','create_time','add_time','status']`
- **复制字段**：COPY_FIELDS_LANG/SELF 由 publish_time 改为 create_time（跨语言/本语言复制保留发布时间）
- **前台键**：`{$list.create_time}` / `{$content.create_time}` = 发布时间（与云优键名一致，无需转换）；建立时间用 `{$vo.add_time}`；YunuService 补注入 `publish_time` 兼容键（旧模板仍可用）
- **排序**：`orderby="create_time desc"`、`order="sort desc, create_time desc, id desc"`（publish_time 写法仍兼容）
- **导入/转换**：yunu_import.php、migrate_yunu_to_yiyun.sql、yunu_tpl_convert.py、install.sql 均已同步；模板调用 `{$vo.publish_time}` → `{$vo.create_time}`
- **验证**：三份 install.sql 无 publish_time 残留；模板无 publish_time 残留；php -l 全通过

### v3.8.1 缩略图/发布时间字段标识对齐云优（2026-10-09）
- **缩略图**：`thumb` → **`pic`**（后台表单 name=pic，collectPost 映射 pic→thumb 列；模型字段管理页 defaultFields 同步）
- **发布时间**：`publish_time` → **`update_time`**（后台表单 name=update_time_dt，JS 提交 `fd.set('update_time', ...)`，collectPost 映射 update_time→publish_time 列；add/edit 保存兼容读 update_time）
- **YunuService::mapContent**：补注入 `update_time` 键（= 发布时间值），云优模板 `{$vo.update_time}` 直接可用
- **不受影响**：内容列表快捷编辑（data-field 直接走 publish_time 列名）；原生模板 `{$vo.publish_time}` 直调仍可用（列保留）
- **验证**：映射单测 PASS（pic→thumb、update_time→publish_time）；php -l 全部通过

### v3.8 字段标识统一为云优命名（2026-10-09）
- **背景**：用户要求后台字段标识与云优完全一致，模板无感调用。
- **字段改名**（表单 name 与 defaultFields）：
  - 短标题 → **副标题**（`short_title` → `ftitle`）
  - 文章简介/产品简介 → **描述**（`description` → `desc`，富文本）
  - 图片集(多图)（`image` → `piclist`，gallery 多图）
- **存储映射**：`Content::collectPost()` 增加字段名映射——提交 `ftitle`→内容表 `short_title` 列、`desc`→`description` 列、`piclist`→`image` 列；编辑回填仍读表列（`{$info.short_title}` 等）。
- **模型字段表去重**：`yy_cn_model_field`/`yy_en_model_field` 中重复 3 组的 `ftitle/desc/piclist` 记录已删除（该三字段归 defaultFields 代码管理，发布页不再重复显示"扩展字段"）。
- **YunuService::mapContent**：补注入 `image` 键（thumb 优先，否则取 piclist 首图），列表模板 `{if $vo.thumb}{$vo.thumb}{else}{$vo.image}{/if}` 兜底生效。
- **模板统一**：`show.html`/`show_news.html` 信息摘要 `{$content.description}` → `{$content.desc|raw}`（单页富文本摘要正常渲染）。
- **验证**：映射单测 PASS（ftitle/desc/piclist→short_title/description/image）；php -l 全部通过；重建 56 页——产品详情轮播 12 图正常、公司简介信息摘要 desc 富文本正常、列表缩略图 fallback 正常。

### v3.7.8 补充：留言表单乱码与样式（2026-10-09）
- **乱码根因**：`show.html`/`show_news.html` 动态留言板 JS（renderBoard）把必填标记 `<span style="color:#e5533d"> *</span>` 拼进输入框 `placeholder` 属性，浏览器把 HTML 当文本显示，字段标签与输入框混成一团。
- **修复**：重写 renderBoard——字段输出改为 `.mf` 结构（`<label>称呼 <em>*</em></label>` + 输入框 `placeholder="请输入称呼"`），label 与输入框分行显示；radio/checkbox 选项改为行内 label 结构。
- **样式缺失**：留言表单类（.msg-form/.mf/.msg-btn/.msg-query/.msg-list/.msg-tip/.panel-sd-tit 等）在云优 CSS 中无定义，表单裸奔。已在 `yunu.css` 末尾补云优风格留言板样式（蓝色主题按钮、边框输入框、查询/列表模块排版）。
- **单页信息区**：`show.html` 信息区（作者/发布时间/点击/发稿人/审核人）加 `{if !$is_single}` 条件——在线留言/公司简介等单页不再显示文章详情元素，文章详情页（is_single=false）保留。
- **验证**：浏览器实拍 `/message.html`——字段"称呼 *(必填)/联系电话/电子邮箱/留言主题/留言内容 *"与输入框分行显示、无乱码；提交留言/查询回复/最新留言模块样式正常。

### v3.7.8 在线留言页面修复（2026-10-09）
- **残留旧表单**：`show.html` 留言板区残留云优转换的旧固定字段表单（msg_default，board_id=1 写死、无公开选项/验证码），与新版动态留言板（msg-form 按后台字段渲染）同页重复显示。
- **修复**：删除 msg_default 旧表单及其依赖 JS（msgSubmit1/msgRefresh）；保留"查询回复"（留言码查询）与"最新留言"（公开留言列表）模块；面板标题由写死的"在线留言12"改为"在线留言"。
- **验证**：`/message.html` 仅 1 个动态留言表单（字段来自 /message/board 接口）；提交测试返回查询码 GDCYRBFS；查询接口返回该留言审核状态"待审核"；测试数据已清理。

### v3.7.7 列表分页修复（2026-10-09）
- **分页 404 根因**：模板内联 `{yunu:list pagesize="6"}` 每页 6 条（8 条产品 = 2 页），但静态生成按栏目 pagesize（10）只生成 1 页 → `page/2.html` 缺失 404。
- **修复**：`StaticPage::category()` 生成前解析模板文件内 `{yunu:list pagesize="N"}`，模板写了则以模板为准计算总页数并逐页生成（栏目未写才用栏目 pagesize）。实测 `/pro/pro1/` 第 1 页 6 条、`/pro/pro1/page/2.html` 2 条均 200。
- **分页无样式根因**：转换时丢了云优模板自带的分页内联 CSS。
- **修复**：`yunu.css` 末尾补云优风格分页样式（`.pages .pagination li a/span` 边框灰底、hover 蓝边、当前页蓝底白字、disabled 灰），与源站 chthy.com 分页视觉一致。

### v3.7.6 静态详情页上一页/下一页修复（2026-10-09）
- 根因：`StaticPage::content()` 未注入云优键 `nexturl/nexttitle/prevurl/prevtitle`（动态 Show 控制器有），静态详情页上下条链接为空。
- 已补注入（同栏目、同状态、按 id 相邻）；实测 106 上一条=105 苏家屯华夏、105 下一条=106 浑河西峡谷。

### v3.7.5 产品详情页三项修复（2026-10-09）
- **多图轮播**：产品详情页图集只显示一张的根因是模板用 `|str2arr='***'`（云优字符串分隔）但易云注入的 `piclist` 是数组。三处注入（YunuService::mapContent / Show / StaticPage::content）统一改为 `implode('***', piclist)` 字符串，与云优模板 str2arr 写法完全兼容；8 条产品已补图集数据（主图+源站工程实景图，后台图片集字段可改）。
- **正文 p 标签显示为文字**：`{$content.content|sitelink}` 缺 `|raw`，ThinkPHP 默认转义导致 `<p>` 变成 `&lt;p&gt;`。已改为 `{$content.content|sitelink|raw}`。
- **本文标签（tag）不解析**：`{yunu:tag title="$content['tag']"}` 中 `$content['tag']` 不含 `{$` 前缀，被当作字面量字符串输出。tagTag 增加 `$[a-zA-Z_]` 变量识别，兼容 `{$content.tag}` 与 `$content['tag']` 两种写法（106 实测输出"传感器"内链）。
- **相关服务空链接**：模板中 `{yunu:cwkeywords /}` + 多余闭合标签导致空 li 与残留文字，已删除整个相关服务块（cwkeywords 本为兼容空标签）；相关产品/相关新闻保留。

### v3.7.4 补充：顶部 Logo 与产品详情页同步（2026-10-09）
- **顶部 logo**：源站 logo 图已下载至 `public/upload/image/20260720/logo_c05f12df.png`，`site.logo` 设置已指向；header 渲染正常。
- **产品详情页**：
  - 根因：静态生成（`StaticPage::content()`）未注入云优键 `pic/piclist/desc/click/ftitle`（动态 Show 控制器有），导致静态产品详情轮播图空。已补齐注入，与动态一致。
  - **数据说明**：云优源站产品详情本就无正文（`yunu_diy_product` 表空、源站产品详情 404），已按辰宏体育业务为 8 条产品补充描述+正文（占位文案，后台可随时修改）。
- **内页 banner 顺带修复**：默认 `banner.jpg` 原为导入的演示站图（"电子科技时代/光模块"），已替换为源站内页 banner（`/uploads/image/20260722/39998448....jpg` 体育场实景），模板加 `?v=20260722` 版本参数防缓存；旧图备份为 `banner_backup_demo.jpg`。

---

## v3.7.3 内页修复：公司简介/联系我们空白、产品三级导航、产品详情（2026-10-09）

### 一、公司简介 / 联系我们 / 企业文化空白修复
- **根因① 跳转目标 @ID 未映射**：云优跳转栏目 external_url 存的是云优栏目 ID（如 `@7`），易云无 id=7 → 跳转目标解析失败 → 回落空列表页。已修复数据库（59/62/64 → @67/@77/@84，77-83 → @67）并给导入脚本 `tools/yunu/yunu_import.php` 增加 `@云优ID → @易云ID` 自动映射（复用 catMap）。
- **根因② 单页 ename 与控制器冲突**：云优单页 etitle 为单段英文（about/contact/culture）→ 易云伪静态把 `/about` 当控制器名 → 报错。已将单页栏目 ename 调整为 aboutus/contactus（主 URL），redirect 顶级改为 company/contact（别名跳转）。
- **根因③ 静态路径带跳转父级**：`category_path()` 原样拼接所有父级 ename，redirect 父级（company/contact）进入路径 → 单页生成到 `cn/company/aboutus.html`。已修改：**跳转/外链父级不参与静态路径**（while 循环跳过 redirect/external）。
- **根因④ 单页缺完整 head**：`template/cn/show.html` 只有导航片段无 `<!DOCTYPE>/<head>/charset` → 浏览器按 GBK 解码 UTF-8 页面出现乱码。已补完整 head（含 charset、SEO、云优静态资源）。
- **配套**：单页静态双写 `aboutus.html` + `aboutus/index.html`（无后缀 `/aboutus` 与 `/aboutus.html` 均可访问）；Lists.php / StaticPage.php 单页 content 数组补 keywords/desc 键。
- **结果**：`/aboutus`、`/aboutus.html`、`/contactus`、`/culture` 全部 200 且正文完整（辰宏体育公司介绍）。

### 二、产品三级导航修复
- **根因**：`YunuService::mapCategory()` 子栏目键名为 `children`，云优模板调用 `{$catlist.child}`（云优键名 child）→ 模板取不到 → 三级导航不显示。
- **修复**：mapCategory 输出 `child` 兼容键（= children），catlist 补全子级时同步写 child 键。
- **结果**：`/pro/pro1/` 侧边栏显示 人造草坪 ▸ 温度传感器1、塑料跑道…，与源站 chty.com 一致。

### 三、产品详情页
- **现状**：轮播图（单图 = 云优缩略图）、标题、面包屑、上下条完整正常；正文为空——**云优源库本无产品正文**（`yunu_diy_product` 表为空、源站产品详情页即 404），无可导入数据。如需产品介绍文字，可在后台内容管理中为 8 条产品补充正文（`yy_cn_content` catid=86）。
- 已确认云优产品 8 条缩略图全部导入（`/upload/image/20260720/*.jpg` 200）。

### 四、无感转换增强（导入脚本）
- `yunu_import.php`：跳转栏目 external_url 的 `@云优ID` 自动映射为易云 ID（复用 catMap，跳转链 62→77→67 全通）。
- 单页静态 URL 规则：主语言 `/aboutus`（双写兼容）+ 非主语言 `/{lang}/aboutus.html`。

### 五、本轮修改文件清单（已同步 F 盘权威副本）
| 文件 | 修改 |
|---|---|
| `app/common.php` | category_path 跳过 redirect/external 父级 |
| `app/common/service/StaticPage.php` | 跳转栏目跳过静态生成；单页双写 index.html；content 数组补 keywords/desc |
| `app/common/service/YunuService.php` | mapCategory 补 child 兼容键 |
| `app/index/controller/Lists.php` | 单页 content 数组补 keywords/desc |
| `template/cn/show.html` | 补完整 head（charset/SEO/静态资源） |
| `tools/yunu/yunu_import.php` | external_url @ID 自动映射 |
| 数据库 `yy_cn_category` | 59/64/67/77-83/84 ename 与 external_url 修正 |

> 注：静态生成命令 `php think build:static cn`（当前 56 页）；改 PHP 后需清 `runtime/index/temp` + `runtime/cache`。
