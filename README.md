# 易云 CMS（YouYun CMS）

参照 **ESPCMS** 功能架构、**云优 CMS** 后台风格，从零实现的一款现代企业建站系统（ThinkPHP 6.1 多应用实现）。

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



* **框架**：ThinkPHP 6.x（topthink/framework 6.1.5）+ think-multi-app 多应用

* **语言**：PHP 8.0+（ThinkPHP 6.1 要求；已在 PHP 8.2.34 验证）

* **数据库**：MySQL 5.7 / 8.x（utf8mb4）

* **模板**：think-template + 自定义标签库（`{cms:xxx}` + 云优兼容 `{yunu:xxx}`）

* **验证码**：think-captcha

* **富文本编辑器**：UEditor（云优 CMS 同款，后台正文 / 描述 / 单页 / 自定义块；支持全屏、附件改名下载、多图上传）



***

## 二、功能清单

### 后台管理（简洁大气，参考云优 CMS）
* **站群系统（v3.18 + v3.20）**：站群开关（系统设置 → 网站设置）；**路径模式**——主域名 + 路径别名即独立站点（`newcms.com/shenyang/`），新建时**自动克隆整套数据库（含数据）+ 复制模板文件夹**；**域名模式**——按 Host 精确/通配匹配；站内链接自动带 `/{别名}/` 前缀；静态生成互不覆盖（`public/{别名}/`）；**站群推送（v3.20）**——分站可向主站推送文章（主站管理员审核通过后发布到主站）、主站也可向分站推送文章（分站管理员采纳后发布），采纳/通过即跨语言复制生成内容副本，支持拒绝（填原因）与撤回；**分站管理员（v3.20）**——管理员可设「可管理站点/语言范围」（`site_scope`），分站管理员登录后顶栏语言、栏目、内容全部隔离在其授权站点内，投稿员/审核员等角色与 `need_audit` 审核开关通用



* 管理员登录 / 退出（验证码）

* 控制台首页

* **语言版本管理（v3.18 增强）**：后台**自由添加 / 编辑 / 删除语言版本**（如 en、ru），每个语言可单独指定 \*\* 前台模板文件夹名（tpl\_dir）\*\* 与前台 `template/{tpl_dir}` 目录联动；新增语言**自动克隆源语言整套数据表（含数据）**；语言列表行内「**复制模板**」按钮从现有语言/站群站点复制整个模板目录（无需手动建目录）；可设默认语言、启用 / 停用、排序；顶栏语言切换录入（每语言独立数据表）

* **栏目管理**：无限级树形、类型（列表 / 单页 / 外链 /**跳转**）、排序、导航 / 显示开关、SEO 设置、**跨语言复制**；列表页**别名 ename 内联编辑**（无需进入编辑页），改别名后按新别名重新静态生成目录

* **内容管理**：多栏目多模型、发布 / 编辑、**富文本编辑器**、扩展字段、批量置顶 / 推荐 / 热门 / 状态 / 删除、浏览自增、**跨语言复制**

* **留言板块**：前台留言、后台列表 / 回复 / 审核 / 删除

* **自定义块**：唯一标识、富文本、按名输出到模板

* **广告位**：唯一标识、按名输出到模板

* **友情链接**：增删改查排序

* **模板管理**：按语言 tpl\_dir + PC/wap 列出目录、在线编辑（防目录穿越）

* **静态页面生成**：按语言（全部语言）生成首页 / 栏目 / 内容 / 全站，目录按 \*\* 栏目别名 (ename)\*\* 生成

* **网站设置**：分组表单（站点基础、SEO、留言、分页条数、**手机版开关**、**自动跳转开关**、上传、后台路径等）

* **管理员管理**：账号 CRUD、修改密码

* 后台操作日志

### 前台（模板化 + 多语言 + 手机版）



* **多语言（自由扩展）**：默认语言（cn）走裸路径 `/`；其他语言带前缀 `/en/`、`/ru/`…；数据表按语言完全隔离（栏目 id 各自从 1 开始）；前台模板目录 `template/{tpl_dir}` 可后台绑定，手机模板 `template/{tpl_dir}/wap/`

* **跨语言复制**：把中文栏目 / 内容一键复制到其他语言栏目，再到对应语言中翻译

* **栏目跳转**：栏目类型可选「跳转」，可设置外部链接或 `@栏目ID`/`@栏目ename` 内部跳转（如一级 "关于我们" 跳转到二级 "公司简介"）

* **手机版**：后台「启用手机版前端」+「自动识别 PC / 手机跳转」开关；手机模板目录 `template/{tpl_dir}/wap/`；支持手动切换电脑版 / 手机版

* **前台搜索**：全站 / 指定栏目，标题 / 正文 / 标题 + 正文，分页（每页条数后台可设，默认 10）

* **列表分页**：后台「列表分页」可设每页条数，默认 10

* **商城系统（v3.15/3.17）**：会员商城（商品价格/购物车/订单/售后/钱包）+ **规格 SKU**（规格组合独立价格与库存，前台选规格联动价格/库存/禁选，下单事务行锁原子扣减防超卖）+ **真实支付商户号**（微信 Native 扫码 / 支付宝当面付，后台填商户号即自动切换真实二维码；未配置自动降级模拟收银台；支持原路退款（微信需证书）与回调验签 /shop/notify_wechat、/shop/notify_alipay；本地环境支付页 5 秒轮询完成订单）

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

## v3.16 系统功能总开关（2026-10-12）

后台「网站设置 → 功能开关(全局)」新增五个独立总开关，关闭后**后台对应菜单自动隐藏、前台对应页面禁止访问**：

| 配置键 | 功能 | 关闭后的效果 |
| --- | --- | --- |
| `member.open` | 会员系统 | 前台 `/member/*`（登录/注册/会员中心/钱包等）全部显示「功能未开启」提示页；后台「会员管理/会员设置」菜单隐藏 |
| `shop.open` | 商城系统 | 前台 `/shop/*`（商品/购物车/结算/订单/支付/售后）禁止访问；后台「商城管理」整组（商品价格/订单/售后/钱包/支付设置）隐藏；详情页与列表页不再注入价格/购物车 |
| `service.open` | 客服系统 | 前台 `/service/chat` 禁止访问；后台「客服会话」菜单隐藏；全站「在线客服」入口隐藏 |
| `comment.open` | 评论功能 | 详情页不注入评论数据、评论块整体隐藏；`/show/comment` 提交接口拒绝；后台「评论管理」菜单隐藏 |
| `favorite.open` | 收藏功能 | 详情页收藏按钮隐藏；`/api/favorite` 提交拒绝、`/api/favorite_count` 恒返回 0 |

实现要点：
- 配置存 `yy_config`（`database/upgrade_v316.sql` 幂等写入，默认全部开启=1）。
- 后台：`AdminBase::applySystemSwitches()` 按 URL 前缀移除关闭功能的菜单项，组内清空则整组隐藏。
- 前台：`FrontBase::initialize()` 注入 `$sys_open` 数组（member/shop/service/comment/favorite 布尔）供模板隐藏入口；控制器级拦截 member/shop/service，关闭时渲染「功能未开启」提示页（优先 `template/{语言}(/wap)/sys_close.html`，无则内置 HTML）。
- 详情/列表/首页：`Show`、`Lists`、`TagService`（cms:list 标签价格注入）、`StaticPage` 均按开关跳过对应数据注入与静态生成。
- 模板：`header`（登录/注册/购物车/客服/会员下拉）、`index_index`（进入商城/注册会员/在线客服按钮 + 平台功能三卡）、`show_product`（购物车/收藏/客服卡/评论块）、`show/show_article/show_pic`（评论块）、`list_product/list_pic`（客服卡）均按 `$sys_open` 条件显示；cn 与 cn/wap 同步。
- 新增前台模板 `template/{语言}/sys_close.html`（可选，含 include header/footer 的统一风格提示页）。
- 三副本代码 + `database/upgrade_v316.sql` + 三份文档已同步；升级执行：`mysql -uroot 库名 < database/upgrade_v316.sql`。

---

## v3.19 后台全平台自适应（2026-10-12）

后台全站响应式布局（百分比 + CSS 变量 + media queries，无框架）：

- **≥1920 超宽屏**：侧边栏 256px、内容区居中最大 1720px、字号/控件放大。
- **1200~1920 笔记本**：侧边栏 200px、控件适度收紧。
- **≤992 平板横屏/小屏**：侧边栏收起为抽屉（左上角汉堡按钮 + 遮罩，点遮罩/Esc/导航项自动关闭），主区全宽。
- **≤768 手机/平板竖屏**：表单竖排、表格横向滚动、统计卡两列、头部按钮压缩、昵称隐藏、语言下拉屏幕固定。
- **≤576 小手机**：仅保留语言切换与退出。
- 文件：`app/admin/view/layout.html`（汉堡按钮/遮罩/header-left）、`public/static/admin/css/admin.css`（末尾 v3.19 响应式块）、`public/static/admin/js/admin.js`（`yy.toggleSidebar`）。
- 已实测：927px 视口下汉堡展开/收起、遮罩开关、栏目/内容管理页无溢出、无横滚。三副本代码 + 三份文档已同步。

---

## v3.18 站群系统（路径模式 + 域名模式，2026-10-12）

**核心能力：无需额外绑定二级域名，用「主域名 + 路径别名」生成独立站点。**

- **站点模式**：`mode=path`（路径模式）——站点别名 `shenyang` → 访问 `http://newcms.com/shenyang/` 即进入该站点；`mode=domain`（域名模式）——按 Host 精确/通配匹配。
- **新建站点一键复制整套**：路径模式新建时自动 ①注册语言版本（code=alias）②**克隆源语言整套数据库**（`yy_{alias}_*` 全部表含数据，id 各自从 1 开始，与主站完全独立）③**复制模板文件夹**（`template/源` → `template/{alias}`，PC+手机版+css/js），前台立即渲染。
- **多语言模板复制**：语言版本列表 / 站群列表行内「复制模板」按钮，从现有语言/站点复制整个模板目录，无需手动建目录。
- **URL 规则**：站群站内所有链接自动带 `/{alias}/` 前缀；站点语言切换按站点白名单；后台切换站点语言录入走独立数据表。
- **静态生成**：站群站点全部静态页输出 `public/{alias}/`（首页 `index.html`、栏目 `pro/index.html` 等），与主站静态完全隔离、互不覆盖；生成时自动注入站点上下文。
- **开关**：系统设置 → 网站设置 → 站群设置(全局) `site.multi`。
- **文件**：`app/admin/controller/Site.php`（重写）、`app/admin/view/site/*`、`app/common.php`（`site_match/site_path_prefix/copy_dir/lang_clone_tables`）、`app/common/middleware/LangRouter.php`、`app/common/service/StaticPage.php`（`pubRoot/relPath/buildLang`）、`app/admin/controller/Language.php`（`copyTpl`）、`app/admin/view/language/index.html`、`template/cn/header.html`（语言切换动态化，已同步 shenyang）、`database/upgrade_v318.sql`（幂等：yy_site 加 alias/mode）。
- 三副本代码 + `database/upgrade_v318.sql` + 三份文档已同步；升级执行：`mysql -uroot 库名 < database/upgrade_v318.sql` 后到后台「站群管理」添加站点。

---

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

---

## v3.20 站群推送与分站管理员（2026-10-12）

### 一、功能
1. **站群双向推送**：分站 → 主站（投稿）：主站管理员审核通过后以副本发布到主站；主站 → 分站（下发）：分站管理员采纳后以副本发布到分站。发起入口：内容管理 → 勾选内容 → 「推送到站点」（选目标站点 + 目标栏目）。
2. **推送状态机**：待处理（0）→ 已采纳发布（1）/ 已拒绝（2）/ 已撤回（3）。采纳 = `copyContent` 在目标语言表生成内容副本并回填 `target_content_id`；拒绝必填原因；撤回仅发起方可操作待处理记录。
3. **分站管理员（站点/语言范围）**：管理员编辑页新增「可管理站点/语言」复选组（`yy_admin.site_scope` JSON 数组，留空=全部）；非超级管理员且非空时，后台语言切换列表与 `admin_sites` 按范围过滤——分站投稿员只能进入分站后台、只能管理分站栏目与内容；角色权限与 `need_audit` 审核开关完全沿用。

### 二、新增/修改文件（运行版已就位，已同步两份 F 盘副本）
| 文件 | 说明 |
|---|---|
| `database/upgrade_v320.sql` | 建 `yy_push` 全局共享表 + `yy_admin.site_scope` 列（幂等） |
| `app/common/model/Push.php` | 推送模型：`siteToLang/createPush/copyContent` |
| `app/admin/controller/PushSite.php` | 推送管理：index/create/audit/withdraw/cats（菜单：扩展功能 → 推送管理） |
| `app/admin/view/push_site/index.html` | TAB 列表 + 采纳/拒绝/撤回弹窗（JS 变量用 json_encode 注入防模板误解析） |
| `app/admin/view/content/index.html` | 批量操作新增「推送到站点」按钮 + 推送弹窗（`PUSH_SITES` 注入） |
| `app/admin/controller/Content.php` | 新增 `pushToSite/pushSites`，index 注入 push_sites |
| `app/admin/controller/AdminBase.php` | initialize 按 site_scope 过滤语言切换列表 + 注入 admin_sites；菜单加「推送管理」 |
| `app/admin/controller/Admin.php` | site_scope 保存/新增 + site_options/cur_scope 注入 |
| `app/admin/view/admin/edit.html` | 「可管理站点/语言」复选组 |

### 三、已实测（浏览器，双向闭环）
- 主站→分站：内容 #211 → 推送到 shenyang → 采纳 → `yy_shenyang_content` 新增 id=212（catid=60），yy_push#1 status=1；
- 分站→主站：分站内容 #212 → 推送到 main → 审核通过 → `yy_cn_content` 新增 id=212（catid=60），yy_push#2 status=1；
- 拒绝：填原因落库（status=2）；撤回：发起方操作成功（status=3）；
- 分站管理员：新建投稿员 `fy`（role=tougao，site_scope=["shenyang"]）→ 登录后顶栏语言仅 SHENYANG、菜单仅内容管理、内容列表仅分站数据。

### 四、二次开发注意
- 模板引擎会误解析 `<script>` 内 JS 对象字面量（`{url:...}` 等）：脚本内一律用 `{:json_encode()}` 注入变量，JS 内组装。
- `AdminBase::ok()/fail()` 仅返回 code/msg/url，树形数据需直接 `json(['code'=>1,'msg'=>'ok','data'=>...])`。
- `yy` 全局对象无 `get()` 方法，栏目树等 GET 用原生 fetch。
