# 云优CMS → 易云CMS 迁移导入指南

> 目标：**拿到一套新的云优模板文件 + 云优数据库，可一键导入易云 CMS，避免历史上踩过的所有坑**。
> 适用版本：易云 CMS v3.8+（ThinkPHP 6、PHP 8.x、MySQL 5.7/8.x）。

---

## 一、文件清单

| 文件 | 作用 |
|---|---|
| `yunu_import.php` | 云优数据库（`yunu_*` 表）→ 易云数据库一键导入（PDO，绝无 SQL 拼接错误） |
| `yunu_tpl_convert.py` | 云优前台模板 → 易云模板自动转换（标签/字段/URL/富文本 |raw/路径） |
| `id_map.json` | 云优栏目 ID → 易云栏目 ID 映射（导入脚本自动生成，转换器自动读取） |

---

## 二、导入新云优数据库

```bash
# 基础导入（默认语言 cn，云优库 chty）
php tools/yunu/yunu_import.php

# 常用参数
php tools/yunu/yunu_import.php --yunu-db=新库名            # 指定云优数据库名
php tools/yunu/yunu_import.php --clean                     # 清空目标语言旧栏目/内容后导入（重装用）
php tools/yunu/yunu_import.php --with-message              # 同时导入云优留言到易云默认留言板
php tools/yunu/yuno_import.php --no-files                  # 跳过云优图片文件复制（路径已改写，文件需自备）
php tools/yunu/yunu_import.php --lang=en                   # 导入到 en 语言（各语言独立库，栏目 ID 各自从 1 计数）
php tools/yunu/yunu_import.php --yunu-user=root --yunu-pass=密码 --yunu-host=127.0.0.1
```

### 自动完成的事项（无需手工 SQL）

1. **模型映射**：云优 `article/product/image/page` 模型 → 易云内置模型；`productdiy` 等自定义模型自动新建；云优 diyfield 扩展字段 → 易云 model_field。
2. **栏目导入（按层级）**：标题/别名/模板/SEO/跳转链接全量；`etitle` 多级路径自动取叶子名；`jumpurl` 的 `@云优栏目ID` 自动映射为 `@易云栏目ID`；非 `--clean` 模式同名同父栏目自动复用（防重复导入）。
3. **内容导入**：短标题(ftitle)→short_title、描述(desc)→description、缩略图(pic)→thumb；**图片集双写**：image 列（`***` 分隔，后台编辑回显）+ extend.piclist（数组，前台云优键），前后台口径永远一致。
4. **富文本实体规范化**：源库 `&lt;p&gt;` 等 HTML 实体自动还原为真实标签（配合模板 `|raw`，前台不会再显示 p 标签文本）。
5. **自定义块 / 广告（轮播）/ 友情链接**：全量导入。
6. **留言导入（可选）**：云优留言 → 易云默认留言板，自动生成查询码。
7. **图片文件复制**：云优 `uploads/` 目录 → 易云 `public/upload/`（路径自动改写 `/uploads/image/` → `/upload/image/`）。
8. **自动生成 `id_map.json`**：供模板转换器把模板里的云优栏目 ID（cid/typeid/pid）替换为易云 ID。

### 导入后必须做的三步

```bash
# 1. 清缓存
cd D:\phpstudy_pro\WWW\newcms
Remove-Item -Recurse -Force runtime\cache, runtime\index\temp -ErrorAction SilentlyContinue

# 2. 后台「静态生成」或命令行重建
php think build:static cn

# 3. 前台 Ctrl+F5 强刷验证
```

> 注意：静态 URL 规则（主语言不带语言前缀 / 非主语言带前缀）自动生效；栏目静态文件按**栏目别名**生成文件夹；列表分页 `/栏目路径/page/2.html` 自动生成。

---

## 二·实战：全新站点一键迁移（含 data.sql 场景）

> 场景示例：易云文件放在 `D:\phpstudy_pro\WWW\ceshinew`，数据库 `ceshinewsql`；云优包在 `D:\phpstudy_pro\WWW\yunubao`（根目录 `template` 放模板、`data.sql` 是云优数据库）。

### 第 1 步：部署易云 CMS

```powershell
# 1. 建库并导入易云初始结构（含默认管理员/设置/中英文基础表）
D:\phpstudy_pro\Extensions\MySQL5.7.26\bin\mysql.exe -uroot -p你的密码 -e "CREATE DATABASE IF NOT EXISTS ceshinewsql DEFAULT CHARACTER SET utf8mb4;"
D:\phpstudy_pro\Extensions\MySQL5.7.26\bin\mysql.exe -uroot -p你的密码 ceshinewsql < D:\phpstudy_pro\WWW\ceshinew\database\install.sql

# 2. 改 .env 数据库配置
#    D:\phpstudy_pro\WWW\ceshinew\.env
#    DATABASE = ceshinewsql

# 3. phpstudy 站点：域名指向 D:\phpstudy_pro\WWW\ceshinew\public，PHP ≥ 7.4（建议 8.x），伪静态开启
```

### 第 2 步：导入云优数据库（data.sql 自动导入临时库）

```powershell
cd D:\phpstudy_pro\WWW\ceshinew
D:\phpstudy_pro\Extensions\php\php8.2.9nts\php.exe tools/yunu/yunu_import.php `
  --yunu-sql=D:\phpstudy_pro\WWW\yunubao\data.sql `
  --yunu-dir=D:\phpstudy_pro\WWW\yunubao `
  --mysql-bin=D:\phpstudy_pro\Extensions\MySQL5.7.26\bin\mysql.exe
```

脚本自动完成：data.sql → 临时库 → 模型/栏目/内容/图片集双写/富文本实体还原/块/广告/友链/图片文件复制 → 生成 `id_map.json`。
（若 data.sql 已手工导入过某库，可直接 `--yunu-db=该库名`，二选一即可。）

### 第 3 步：转换云优模板

```powershell
cd D:\phpstudy_pro\WWW\ceshinew
python tools/yunu/yunu_tpl_convert.py D:\phpstudy_pro\WWW\yunubao\template D:\phpstudy_pro\WWW\ceshinew\template cn
```

> 云优模板目录说明：若 `yunubao\template` 下还套着 `default\index` 之类子目录，请把该子目录作为第一个参数传入。

### 第 4 步：清缓存 + 静态生成 + 验证

```powershell
cd D:\phpstudy_pro\WWW\ceshinew
Remove-Item -Recurse -Force runtime\cache, runtime\index\temp -ErrorAction SilentlyContinue
D:\phpstudy_pro\Extensions\php\php8.2.9nts\php.exe think build:static cn
```

浏览器访问站点，Ctrl+F5 强刷，按本指南第五节验证清单逐项检查。

---

## 三、导入新云优模板（通用）

```bash
python tools/yunu/yunu_tpl_convert.py [云优模板目录] [易云template目录] [语言目录]
# 示例（默认）：
python tools/yunu/yunu_tpl_convert.py D:\phpstudy_pro\WWW\chty\template\default\index F:\豆包下载\CMS\template cn
```

### 转换器自动完成

| 规则 | 说明 |
|---|---|
| 云优标签 | `<yunu:xxx>` → `{yunu:xxx}`（含自闭合补 `/`） |
| TP3 判断/循环 | `<if>/<elseif>/<else/>/<volist>` → TP6 花括号语法 |
| 栏目 ID | 模板中 `cid/typeid/pid="云优ID"` → 自动替换为易云 ID（读 id_map.json） |
| include | `public:header` 等 → 易云公共模板名 |
| 资源路径 | `__PUBLIC__` 保留（易云模板引擎自动解析为 `/assets/index?path=`，资源复制到 `template/{语言}/` 模板同级）；`/uploads/image/` → `/upload/image/` |
| 字段别名 | `$category.title→name`、`$content.seo_desc→desc` 等；`create_time` 键名与易云一致（发布时间），无需转换 |
| **富文本补 \|raw** | `{$type.desc}`、`{$content.desc}`、`{$category.content}` 等无管道输出自动补 `|raw`（有 `\|str2sub` 截断的不动）——**避免 p 标签显示成文本** |
| 日期管道 | `|date='Y-m-d',###` → TP6 格式 |
| 搜索表单 | `name="key"` → `name="kw"` |

### 模板命名规则（易云自动识别）

| 用途 | 命名 |
|---|---|
| 首页 | `index_index.html` |
| 列表模板 | `list_***.html`（栏目后台选） |
| 内容模板 | `show_***.html`（栏目后台选） |
| 搜索页 | `search_index.html`（优先）/ `search.html` |
| 公共模板 | `header.html`、`footer.html`、`public_banner.html`、`public_left.html` |
| 手机端 | 与 PC 共用同一份模板（自动响应式），无需单独目录 |

> 转换器输出目录默认备份为 `template_backup_<时间戳>`，转换失败可回滚。

---

## 四、历史踩坑 → 避错对照表

| # | 历史错误 | 现在如何避免 |
|---|---|---|
| 1 | SQL 批量 INSERT `#1136 Column count doesn't match value count` | 全部走 PDO 预处理（脚本内无一条手工 SQL 拼接）；导入必须用本脚本，勿手工执行 INSERT |
| 2 | 访问 500 Internal Server Error | 环境要求：PHP ≥ 7.4（建议 8.x）、站点根目录设为 `public`、开启 rewrite、`runtime/` 可写；导入后清缓存 |
| 3 | 后台登录验证码一直错 | 确认 PHP session 正常、浏览器无缓存；若仍异常先清 `runtime/session` 再试 |
| 4 | `url(): Argument #2 ($vars) must be of type array` | 模板转换器把 TP3 `{:url('x','y')}` 类写法规范化为易云 `site_url()`/`url_cat()` 数组参数；新模板转换后不要手工改 URL 函数 |
| 5 | `Call to undefined method think\file\UploadedFile::validate()` | 已修复 Upload 控制器（用 `think\facade\Validate` 或手动校验）；新模板无需处理 |
| 6 | 前台富文本显示 `&lt;p&gt;` 文本 | 双保险：导入脚本 `norm_html()` 还原实体 + 转换器自动补 `|raw` |
| 7 | 前台图集正常、后台图片集空（保存后丢图） | 导入脚本图片集**双写**（image 列 `***` + extend.piclist），编辑页 hidden 用 imageList 拼接回填 |
| 8 | 产品详情多图只显示一张 / 图集拆不开 | 图集统一 `***` 分隔；`{yunu:list}`/`str2arr='***'` 与双写口径一致 |
| 9 | 栏目链接带 `cn` 前缀（主语言） | 静态生成：主语言 URL 不含语言前缀（`/news`），非主语言带前缀（`/en/news`）；伪静态规则自动生成 |
| 10 | 三级栏目详情链接少了上级 | 静态 URL 开关：精准链接（一级/二级/三级/详情）或精简链接（只留最后一级+详情） |
| 11 | 富文本/弹层层级遮挡 | 已统一 z-index 治理；新模板编辑器高度/层级沿用易云公共样式 |
| 12 | 附件上传 405 / 在线管理空白 | UEditor 上传接口已指向易云 Upload 控制器；新环境确认 `public/static/admin/ueditor` 完整 |
| 13 | 列表分页报错/无样式 | 静态分页逐页生成 + `{$page|raw}` 输出 + 云优风格分页样式内置 |
| 14 | 留言板前台乱码 | 留言表单由 `renderBoard()` 动态生成（label+输入框结构），新模板留言区用标准 `{yunu:board}` 调用 |
| 15 | 上下条文章顺序反 | 云优命名与直觉相反：`nexturl`=上一条、`prevurl`=下一条，转换器已兼容 |
| 16 | 多语言栏目 ID 不独立 | 各语言独立库（yy_cn_/yy_en_/yy_ru_…），栏目 ID 各自从 1 计数；跨语言复制内容/栏目时目标库自动新建 |

---

## 五、导入后验证清单

- [ ] 后台「栏目管理」栏目树层级、类型（列表/单页/跳转）、别名、模板选择正确
- [ ] 后台「内容管理」每栏目文章数正确；产品图片集、缩略图、描述、正文正常
- [ ] 前台首页：logo、banner、关于我们（无 p 标签文本）、产品中心、新闻、底部二维码/版权
- [ ] 前台列表页：分页正常、翻页样式；详情页：轮播、正文、上下条
- [ ] 搜索：全站/栏目、标题/正文均可搜出结果
- [ ] 留言板：提交→查询码→后台回复→前台查询/展示
- [ ] 多语言：en 切换正常，静态页 `/en/...` 前缀，主语言无前缀
- [ ] 后台右上角「静态生成」一键重建全站无报错

---

## 六、常见问题（FAQ）

**Q1：导入后栏目为空？** 检查 `--yunu-db` 库名是否正确、源库表前缀是否为 `yunu_`；非 `--clean` 模式同名栏目会自动复用，二次导入不会新增。

**Q2：图片不显示？** 确认图片文件已复制（或 `--no-files` 后手工补齐到 `public/upload/`）；数据库地址应为 `/upload/image/YYYYMMDD/xxx`。

**Q3：模板转换后页面报错？** 看是否手工改过 URL 函数/标签；重新跑转换器（自动备份可回滚），保持转换器输出原样再调样式。

**Q4：要换语言（如加俄语 ru）？** 前台手动建 `template/ru/` 文件夹 → 后台「多语言管理」添加 ru（文件夹名=ru）→ 用 `--lang=ru` 再跑导入脚本 → 重建静态。
