<?php
// +----------------------------------------------------------------------
// | 云优CMS(yunu_*)数据库 → 易云CMS 一键导入脚本
// | 用法（在项目根目录执行）：
// |   php tools/yunu/yunu_import.php                 # 默认导入 cn 语言
// |   php tools/yunu/yunu_import.php --lang=en       # 导入 en 语言
// |   php tools/yunu/yunu_import.php --clean         # 清空目标语言旧数据后导入
// |   php tools/yunu/yunu_import.php --with-message  # 同时导入云优留言
// |   php tools/yunu/yunu_import.php --no-files      # 跳过云优图片文件复制
// |   php tools/yunu/yunu_import.php --yunu-db=chty  # 指定云优数据库名
// |
// | 已内置避错（历史踩坑全部规避）：
// |   ① 全部走 PDO 预处理，绝无 SQL 列数不匹配（#1136）
// |   ② 富文本实体规范化：&lt;p&gt;→<p>（模板 |raw 后不再显示 p 标签文本）
// |   ③ 图片集双写：image 列(***分隔) + extend.piclist(数组)，前后台口径一致
// |   ④ 栏目跳转 @云优ID → @易云ID 自动映射；etitle 多级路径取叶子名
// |   ⑤ 内容写入短标题(ftitle)/描述(desc)/缩略图(pic)/图片集(piclist) 对应易云列
// |   ⑥ 单条失败收集继续，不中断；末尾汇总错误清单
// |   ⑦ 末尾自动生成 id_map.json（云优ID→易云ID），模板转换器直接读取
// |   php tools/yunu/yunu_import.php --yunu-user=root --yunu-pass=19990903 --yunu-host=localhost
// +----------------------------------------------------------------------
// | 映射规则：
// |   模型：diymodel(tabname) → 易云模型(article→1 / product→2 / image→3 / page→1 / productdiy→新建)
// |   栏目：title→name etitle→ename pid→parent_id mid→model_id pic→image seo_desc→description
// |         desc→description jumpurl→redirect+external_url tpl_list→template tpl_show→content_template
// |         status→is_show nav→is_nav
// |   内容：yunu_content + yunu_diy_* 合并 → yy_content
// |         click→views istop→is_top create_time→create_time+add_time pic→thumb
// |         diy.ftitle→short_title diy.author→author diy.desc→description diy.content→content
// |         diy.piclist→image(逗号串)+extend.piclist(数组) 其余diy列→extend JSON
// |   块：yunu_block.url→name type(4/1→text,2→image) content→content/image
// |   广告：yunu_banner.type→name(yunu_1/yunu_2) pic→image url→url
// |   友链：yunu_link.title→name pic→logo url→url
// |   图片路径：/uploads/image/YYYYMMDD/x → /upload/image/YYYYMMDD/x
// +----------------------------------------------------------------------
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
date_default_timezone_set('Asia/Shanghai');

// ---------- 参数 ----------
$args = getopt('', ['lang::', 'clean', 'yunu-db::', 'yunu-user::', 'yunu-pass::', 'yunu-host::', 'with-message', 'no-files', 'yunu-sql::', 'yunu-dir::', 'mysql-bin::', 'help']);
$lang     = $args['lang'] ?? 'cn';
$clean    = isset($args['clean']);
$withMsg  = isset($args['with-message']);   // 同时导入云优留言 → 易云默认留言板
$withFiles= !isset($args['no-files']);      // 是否复制云优图片文件
$yunuDb   = $args['yunu-db'] ?? 'chty';
$yunuUser = $args['yunu-user'] ?? 'root';
$yunuPass = $args['yunu-pass'] ?? '19990903';
$yunuHost = $args['yunu-host'] ?? '127.0.0.1';
$yunuSql  = $args['yunu-sql'] ?? '';    // 云优 data.sql 文件路径（自动导入临时库）
$yunuDir  = $args['yunu-dir'] ?? '';    // 云优包目录（图片源 {dir}/uploads）
$mysqlBin = $args['mysql-bin'] ?? 'mysql'; // mysql CLI 路径（Windows 常见 D:\phpstudy_pro\Extensions\MySQL5.7.26\bin\mysql.exe）

if (isset($args['help'])) {
    echo file_get_contents(__FILE__) === '' ? '' : "云优数据库导入脚本\nphp tools/yunu/yunu_import.php [--lang=cn|en|...] [--clean] [--yunu-db=chty] [--yunu-user=root] [--yunu-pass=xxx] [--yunu-host=127.0.0.1]\n";
    exit(0);
}

// ---------- 读易云 .env ----------
function env_file($path) {
    $map = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $map[trim($k)] = trim($v);
    }
    return $map;
}
$envPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
if (!is_file($envPath)) { fwrite(STDERR, "未找到 .env：{$envPath}\n"); exit(1); }
$env = env_file($envPath);
$dbHost = $env['HOSTNAME'] ?? $env['DB_HOST'] ?? '127.0.0.1';
$dbName = $env['DATABASE'] ?? $env['DB_NAME'] ?? 'newcms';
$dbUser = $env['USERNAME'] ?? $env['DB_USERNAME'] ?? 'root';
$dbPass = $env['PASSWORD'] ?? $env['DB_PASSWORD'] ?? '';

$tablePrefix = 'yy_' . $lang . '_';
$srcPrefix   = 'yunu_';

// ---------- 若提供 data.sql：自动导入临时库（避免手工 SQL，杜绝 #1136 等导入错误） ----------
if ($yunuSql !== '') {
    if (!is_file($yunuSql)) { fwrite(STDERR, "data.sql 不存在：{$yunuSql}\n"); exit(1); }
    $tmpDb = 'yunu_tmp_' . substr(md5(uniqid('', true)), 0, 8);
    $c1 = sprintf('"%s" -h%s -u%s -p%s -e "CREATE DATABASE IF NOT EXISTS %s DEFAULT CHARACTER SET utf8mb4"', $mysqlBin, $yunuHost, $yunuUser, $yunuPass, $tmpDb);
    exec($c1 . ' 2>&1', $o1_, $rc1);
    if ($rc1 !== 0) {
        fwrite(STDERR, "创建临时库失败（请确认 --mysql-bin 指向 mysql.exe）：\n" . implode("\n", $o1_) . "\n");
        exit(1);
    }
    $c2 = sprintf('"%s" -h%s -u%s -p%s %s < "%s"', $mysqlBin, $yunuHost, $yunuUser, $yunuPass, $tmpDb, $yunuSql);
    exec($c2 . ' 2>&1', $o2_, $rc2);
    if ($rc2 !== 0) {
        fwrite(STDERR, "导入 data.sql 失败（请用 mysql CLI 手工导入后改用 --yunu-db）：\n" . implode("\n", $o2_) . "\n");
        exit(1);
    }
    $yunuDb = $tmpDb;
    echo "data.sql 已导入临时库：{$tmpDb}（导入后如需保留可自行转正式库）\n";
}

// ---------- 连接 ----------
$dsnY = "mysql:host={$yunuHost};dbname={$yunuDb};charset=utf8mb4";
$dsnE = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
try {
    $y = new PDO($dsnY, $yunuUser, $yunuPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $e = new PDO($dsnE, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $ex) {
    fwrite(STDERR, "连接失败：{$ex->getMessage()}\n"); exit(1);
}
echo "已连接：云优库 {$yunuDb} → 易云库 {$dbName}（语言 {$lang}）\n";

function yq(PDO $db, string $sql, array $p = []) {
    $st = $db->prepare($sql);
    $st->execute($p);
    return $st;
}
function yall(PDO $db, string $sql, array $p = []) { return yq($db, $sql, $p)->fetchAll(PDO::FETCH_ASSOC); }
function yone(PDO $db, string $sql, array $p = []) { $r = yall($db, $sql, $p); return $r ? $r[0] : null; }

// ---------- 图片路径改写：/uploads/image/YYYYMMDD/x → /upload/image/YYYYMMDD/x ----------
function fix_pic($v) {
    if (is_array($v)) {
        $out = [];
        foreach ($v as $x) $out[] = fix_pic($x);
        return $out;
    }
    $v = (string)$v;
    return preg_replace('#/uploads/image/#', '/upload/image/', $v);
}

// ---------- 富文本规范化：HTML 实体(如 &lt;p&gt;) → 真实标签 ----------
// 修复历史踩坑：模板加 |raw 后仍显示 <p> 文本，是入库时被 htmlspecialchars 转义所致
function norm_html($v) {
    if ($v === null || $v === '') return '';
    $v = (string)$v;
    if (strpos($v, '&lt;') !== false || strpos($v, '&gt;') !== false) {
        // 先转回真实标签再双编码还原其余实体（&amp; &quot; 等）
        $dec = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // 二次解码（源库可能双重转义）
        $dec2 = html_entity_decode($dec, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $dec2;
    }
    return $v;
}

// ---------- 错误收集（单条失败不中断，汇总报告） ----------
$GLOBALS['__err'] = [];
function ynote($kind, $msg) { $GLOBALS['__err'][] = "[{$kind}] {$msg}"; }
function ytry(callable $fn, string $kind, string $tag) {
    try { return $fn(); }
    catch (Throwable $ex) { ynote($kind, "{$tag}：{$ex->getMessage()}"); return null; }
}

// ---------- 易云内置字段（不进入 model_field / extend） ----------
$defaultFields = ['title','short_title','author','source','thumb','image','keywords','description','content','top','is_recommend','is_hot','tag','sort','create_time','add_time','status','etitle','jumpurl'];

// ---------- 1) 模型映射 ----------
echo "\n【1/6】模型映射...\n";
$diymodels = yall($y, "SELECT id,title,tabname,type FROM {$srcPrefix}diymodel ORDER BY id");
$modelMap = [];       // 云优 diymodel.id → 易云 model.id
$tabMap   = [];       // 云优 diymodel.id → tabname
$newModels = [];
foreach ($diymodels as $dm) {
    $tab = $dm['tabname'] ?: ('m' . $dm['id']);
    $tabMap[$dm['id']] = $tab;
    $target = null;
    if ($tab === 'article')       $target = 1; // 易云文章模型
    elseif ($tab === 'product')   $target = 2; // 易云产品模型
    elseif ($tab === 'image')     $target = 3; // 易云图片模型
    elseif ($tab === 'page')      $target = 1; // 单页用文章模型
    if ($target !== null) {
        $modelMap[$dm['id']] = $target;
        echo "  模型 {$dm['title']}(tabname={$tab}) → 易云模型 id={$target}\n";
    } else {
        // 新建模型
        $exist = yone($e, "SELECT id FROM {$tablePrefix}model WHERE name=?", [$tab]);
        if ($exist) {
            $modelMap[$dm['id']] = (int)$exist['id'];
            echo "  模型 {$dm['title']}(tabname={$tab}) → 已存在易云模型 id={$exist['id']}\n";
        } else {
            $t = time();
            yq($e, "INSERT INTO {$tablePrefix}model (name,title,description,list_template,show_template,fields,status,create_time,update_time) VALUES (?,?,?,?,?,?,1,?,?)",
                [$tab, $dm['title'], '', 'list', 'show', '', $t, $t]);
            $modelMap[$dm['id']] = (int)$e->lastInsertId();
            $newModels[] = $dm['id'];
            echo "  模型 {$dm['title']}(tabname={$tab}) → 新建易云模型 id={$modelMap[$dm['id']]}\n";
        }
    }
}

// 字段导入：云优 diyfield（非分隔线、非易云内置）→ 易云 model_field
$ftypeMap = ['text'=>'text','textarea'=>'textarea','editor'=>'rich','seditor'=>'rich','images'=>'gallery','number'=>'number','select'=>'select','radio'=>'radio','checkbox'=>'checkbox'];
$diyfields = yall($y, "SELECT id,mid,title,field,ftype,defval,isnotnull,`values` FROM {$srcPrefix}diyfield ORDER BY mid,id");
$fieldImported = 0;
foreach ($diyfields as $df) {
    if (($df['ftype'] ?? '') === '------') continue;          // 分隔线
    if (!isset($modelMap[$df['mid']])) continue;              // 模型未映射
    $fname = $df['field'];
    if (in_array($fname, $defaultFields, true)) continue;     // 易云内置字段
    if ($fname === 'conid' || $fname === '') continue;
    $t = time();
    yq($e, "INSERT INTO {$tablePrefix}model_field (model_id,name,label,type,options,required,sort,create_time,update_time) VALUES (?,?,?,?,?,?,?,?,?)",
        [$modelMap[$df['mid']], $fname, $df['title'], $ftypeMap[$df['ftype']] ?? 'text', $df['values'] ?? '', (int)($df['isnotnull'] ?? 0), 100 + $fieldImported, $t, $t]);
    $fieldImported++;
}
echo "  字段导入完成：{$fieldImported} 个扩展字段\n";

// ---------- 2) 栏目导入（按层级顺序） ----------
echo "\n【2/6】栏目导入...\n";
if ($clean) {
    yq($e, "DELETE FROM {$tablePrefix}category");
    echo "  已清空旧栏目\n";
}
$cats = yall($y, "SELECT * FROM {$srcPrefix}category ORDER BY pid,id");
$catMap = []; // 云优 category.id → 易云 category.id
$catNew = 0; $catSkip = 0;
// 预取易云现有栏目（非 clean 模式避免重复导入）
$existCats = [];
foreach (yall($e, "SELECT id,name,parent_id FROM {$tablePrefix}category") as $ec) {
    $existCats[$ec['parent_id'] . '|' . $ec['name']] = (int)$ec['id'];
}
foreach ($cats as $c) {
    $pidNew = $c['pid'] ? ($catMap[$c['pid']] ?? null) : 0;
    if ($c['pid'] && !$pidNew) { echo "  跳过：父栏目未导入 id={$c['id']} {$c['title']}\n"; $catSkip++; continue; }
    // 非 clean 模式：同名同父已存在 → 复用
    if (!$clean && isset($existCats[$pidNew . '|' . $c['title']])) {
        $catMap[$c['id']] = $existCats[$pidNew . '|' . $c['title']];
        echo "  复用栏目 id={$existCats[$pidNew . '|' . $c['title']]} {$c['title']}\n";
        $catSkip++; continue;
    }
    $midNew = isset($modelMap[$c['mid']]) ? $modelMap[$c['mid']] : 1;
    // 类型：跳转 > 单页 > 列表
    $type = 'list';
    $extUrl = '';
    if (!empty($c['jumpurl'])) {
        $type = 'redirect'; $extUrl = $c['jumpurl'];
        // 云优 @栏目ID 跳转 → 映射为易云栏目ID（catMap 已含本栏目之前的全部映射）
        if (preg_match('/^@(\d+)$/', $extUrl, $mm) && isset($catMap[(int)$mm[1]])) {
            $extUrl = '@' . $catMap[(int)$mm[1]];
        }
    } elseif (($tabMap[$c['mid']] ?? '') === 'page') {
        $type = 'single';
    }
    $ename = trim((string)($c['etitle'] ?? ''));
    if ($ename === '') {
        $ename = preg_match('/^[A-Za-z0-9_-]+$/', $c['title']) ? $c['title'] : 'cat_' . $c['id'];
    }
    // 云优 etitle 常为完整路径（news/industrynews），易云只存叶子名，层级由 parent_id 推导
    if (strpos($ename, '/') !== false) {
        $ename = substr($ename, strrpos($ename, '/') + 1);
    }
    if ($ename === '') $ename = 'cat_' . $c['id'];
    $tplList = trim((string)($c['tpl_list'] ?? ''));
    $tplShow = trim((string)($c['tpl_show'] ?? ''));
    if ($tplList !== '' && strpos($tplList, '.html') !== false) $tplList = substr($tplList, 0, -5);
    if ($tplShow !== '' && strpos($tplShow, '.html') !== false) $tplShow = substr($tplShow, 0, -5);
    $desc = norm_html((string)($c['seo_desc'] ?? ''));
    if ($desc === '') $desc = norm_html((string)($c['desc'] ?? ''));
    $t = time();
    ytry(function () use ($e, $tablePrefix, $pidNew, $midNew, $c, $ename, $type, $extUrl, $desc, $tplList, $tplShow, $t, &$catMap) {
        yq($e, "INSERT INTO {$tablePrefix}category (parent_id,model_id,cat_model_id,name,ename,subtitle,type,cover,external_url,target,seo_title,keywords,description,image,content,extend,template,content_template,static_path,is_show,is_nav,sort,pagesize,create_time,update_time) VALUES (?,?,0,?,?,?,?,?,?,?,?,?,?,?,?,NULL,?,?,'',?,?,?,10,?,?)",
            [$pidNew, $midNew, $c['title'], $ename, (string)($c['subtitle'] ?? ''), $type, (int)($c['cover'] ?? 0), $extUrl, (int)($c['target'] ?? 0), (string)($c['seo_title'] ?? ''), (string)($c['seo_keywords'] ?? ''), mb_substr($desc, 0, 500), mb_substr(fix_pic((string)($c['pic'] ?? '')), 0, 255), norm_html((string)($c['content'] ?? '')), $tplList, $tplShow, (int)($c['status'] ?? 1), (int)($c['nav'] ?? 0), (int)($c['sort'] ?? 100), (int)($c['create_time'] ?? $t), (int)($c['update_time'] ?? $t)]);
        $catMap[$c['id']] = (int)$e->lastInsertId();
        return $e->lastInsertId();
    }, '栏目', $c['title']);
}
echo "  栏目导入完成：新增 {$catNew}，复用/跳过 {$catSkip}\n";

// ---------- 3) 内容导入 ----------
echo "\n【3/6】内容导入...\n";
if ($clean) {
    yq($e, "DELETE FROM {$tablePrefix}content");
    yq($e, "DELETE FROM {$tablePrefix}content_audit_log");
    echo "  已清空旧内容\n";
}
$diyTables = ['article'=>'yunu_diy_article','image'=>'yunu_diy_image','product'=>'yunu_diy_product','page'=>'yunu_diy_page','productdiy'=>'yunu_diy_productdiy'];
$diyCoreCols = ['conid','ftitle','author','piclist','desc','content'];
$cons = yall($y, "SELECT * FROM {$srcPrefix}content ORDER BY id");
$conNew = 0; $conSkip = 0; $extendCount = 0;
foreach ($cons as $row) {
    if (!isset($catMap[$row['cid']])) { $conSkip++; continue; }   // 栏目未导入
    $tab = $tabMap[$row['mid']] ?? 'article';
    $diyTbl = $diyTables[$tab] ?? null;
    $diy = $diyTbl ? yone($y, "SELECT * FROM {$diyTbl} WHERE conid=?", [$row['id']]) : null;
    // 描述/正文/短标题/作者（富文本做实体规范化，避免前台 |raw 后仍显示 <p> 文本）
    $description = norm_html((string)($diy['desc'] ?? ''));
    if ($description === '') $description = norm_html((string)($row['seo_desc'] ?? ''));
    $content = norm_html((string)($diy['content'] ?? ''));
    $shortTitle = (string)($diy['ftitle'] ?? '');
    $author = (string)($diy['author'] ?? '');
    // 图片集
    $piclistRaw = (string)($diy['piclist'] ?? '');
    $picArr = [];
    if ($piclistRaw !== '') {
        $picArr = fix_pic(array_values(array_filter(array_map('trim', explode('***', $piclistRaw)))));
        $picArr = array_values(array_filter($picArr));
    }
    $thumb = fix_pic((string)($row['pic'] ?? ''));
    // 扩展字段：diy 表非核心列
    $extend = [];
    if ($diy) {
        foreach ($diy as $k => $v) {
            if (in_array($k, $diyCoreCols, true) || $v === null || $v === '') continue;
            $extend[$k] = fix_pic($v);
        }
    }
    if ($picArr) {
        $extend['piclist'] = $picArr;
        $extendCount++;
    }
    $t = time();
    // 图片集双写：image 列（*** 分隔，后台编辑回显口径）+ extend.piclist（数组，前台云优键口径）
    $ok = ytry(function () use ($e, $tablePrefix, $catMap, $modelMap, $row, $diy, $shortTitle, $author, $thumb, $picArr, $description, $content, $extend, $t) {
        yq($e, "INSERT INTO {$tablePrefix}content (catid,model_id,title,etitle,jumpurl,short_title,author,source,thumb,image,keywords,tag,description,content,extend,is_top,is_recommend,is_hot,top,istop,status,views,sort,static_path,create_time,add_time,update_time,author_uid,audit_uid,audit_time,audit_status,submit_count,reject_reason) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,?,?,'',?,?,?,0,0,0,0,0,'')",
            [$catMap[$row['cid']], $modelMap[$row['mid']] ?? 1, $row['title'], (string)($row['etitle'] ?? ''), (string)($row['jumpurl'] ?? ''), mb_substr($shortTitle, 0, 255), mb_substr($author, 0, 50), '', mb_substr($thumb, 0, 255), mb_substr(implode('***', $picArr), 0, 2000), mb_substr((string)($row['seo_keywords'] ?? ''), 0, 255), (string)($row['tag'] ?? ''), mb_substr($description, 0, 500), $content, $extend ? json_encode($extend, JSON_UNESCAPED_UNICODE) : '', (int)($row['istop'] ?? 0), 0, 0, (int)($row['top'] ?? 0), (int)($row['istop'] ?? 0), (int)($row['click'] ?? 0), (int)($row['sort'] ?? 100), (int)($row['create_time'] ?? $t), (int)($row['create_time'] ?? $t), (int)($row['update_time'] ?? $t)]);
        return true;
    }, '内容', (string)$row['title']);
    if ($ok) $conNew++; else $conSkip++;
}
echo "  内容导入完成：新增 {$conNew}，跳过 {$conSkip}（含 {$extendCount} 条图片集/扩展）\n";

// ---------- 4) 自定义块 ----------
echo "\n【4/6】自定义块导入...\n";
if ($clean) {
    yq($e, "DELETE FROM {$tablePrefix}block");
    echo "  已清空旧块\n";
}
$blockMap = ['4'=>'text','1'=>'text','2'=>'image'];
$blocks = yall($y, "SELECT * FROM {$srcPrefix}block ORDER BY id");
$blockNew = 0; $blockSkip = 0;
foreach ($blocks as $b) {
    // 云优块标识在 title 列（tel/mobile/email/...），url 列为空；remark 为中文说明
    $name = trim((string)($b['title'] ?? ''));
    $label = trim((string)($b['remark'] ?? ''));
    if ($label === '' || $label === $name) $label = $name;
    if ($name === '') { $blockSkip++; continue; }
    $exist = yone($e, "SELECT id FROM {$tablePrefix}block WHERE name=?", [$name]);
    if ($exist) { $blockSkip++; echo "  跳过重复块 {$name}\n"; continue; }
    $btype = $blockMap[$b['type']] ?? 'text';
    $content = norm_html((string)($b['content'] ?? ''));
    $image = '';
    if ($btype === 'image') {
        $image = fix_pic($content);
        $content = '';
    }
    $t = time();
    yq($e, "INSERT INTO {$tablePrefix}block (parent_id,name,title,type,content,image,link,sort,status,create_time,update_time) VALUES (0,?,?,?,?,?,'',100,1,?,?)",
        [$name, $label, $btype, $content, $image, (int)($b['create_time'] ?? $t), (int)($b['update_time'] ?? $t)]);
    $blockNew++;
    echo "  + 块 {$name} [{$btype}] {$label}\n";
}
echo "  块导入完成：新增 {$blockNew}，跳过 {$blockSkip}\n";

// ---------- 5) 广告（轮播） ----------
echo "\n【5/6】广告/轮播导入...\n";
if ($clean) {
    yq($e, "DELETE FROM {$tablePrefix}ad");
    echo "  已清空旧广告\n";
}
$banners = yall($y, "SELECT * FROM {$srcPrefix}banner ORDER BY id");
$adNew = 0;
foreach ($banners as $bn) {
    $name = 'yunu_' . (int)($bn['type'] ?? 1);
    $t = time();
    yq($e, "INSERT INTO {$tablePrefix}ad (parent_id,name,title,width,height,content,image,url,sort,status,create_time,update_time) VALUES (0,?,?,0,0,'',?,?,?,1,?,?)",
        [$name, (string)($bn['title'] ?? ''), mb_substr(fix_pic((string)($bn['pic'] ?? '')), 0, 255), (string)($bn['url'] ?? '#'), (int)($bn['sort'] ?? 100), (int)($bn['create_time'] ?? $t), (int)($bn['update_time'] ?? $t)]);
    $adNew++;
}
echo "  广告导入完成：新增 {$adNew}（name: yunu_1=PC组 / yunu_2=WAP组）\n";

// ---------- 6) 友情链接 ----------
echo "\n【6/6】友情链接导入...\n";
if ($clean) {
    yq($e, "DELETE FROM {$tablePrefix}link");
    echo "  已清空旧友链\n";
}
$links = yall($y, "SELECT * FROM {$srcPrefix}link ORDER BY id");
$linkNew = 0;
foreach ($links as $lk) {
    $t = time();
    yq($e, "INSERT INTO {$tablePrefix}link (name,url,logo,description,sort,status,create_time,update_time) VALUES (?,?,?,'',?,1,?,?)",
        [(string)($lk['title'] ?? ''), (string)($lk['url'] ?? ''), mb_substr(fix_pic((string)($lk['pic'] ?? '')), 0, 255), (int)($lk['sort'] ?? 100), (int)($lk['create_time'] ?? $t), (int)($lk['update_time'] ?? $t)]);
    $linkNew++;
}
echo "  友链导入完成：新增 {$linkNew}\n";

// ---------- 图片文件复制（--no-files 跳过） ----------
$srcUpload = $yunuDir !== ''
    ? rtrim($yunuDir, '\\/') . DIRECTORY_SEPARATOR . 'uploads'
    : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'chty' . DIRECTORY_SEPARATOR . 'uploads';
// 兼容：若 {dir}/uploads 不存在，尝试 {dir}/upload 或 {dir}/statics/uploads
if ($yunuDir !== '' && !is_dir($srcUpload)) {
    foreach (['upload', 'statics' . DIRECTORY_SEPARATOR . 'uploads', 'uploads' . DIRECTORY_SEPARATOR . 'image'] as $sub) {
        $try = rtrim($yunuDir, '\\/') . DIRECTORY_SEPARATOR . $sub;
        if (is_dir($try)) { $srcUpload = $try; break; }
    }
}
$dstUpload = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'upload';
if (!$withFiles) { echo "\n图片文件复制：已跳过（--no-files）\n"; }
else {
echo "\n图片目录检查：云优 {$srcUpload}";
if (is_dir($srcUpload)) {
    $n = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcUpload, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile()) {
            $rel = substr($f->getPathname(), strlen($srcUpload));
            $rel = preg_replace('#^[\\\\/]+#', '', $rel);
            $dst = $dstUpload . DIRECTORY_SEPARATOR . $rel;
            $d = dirname($dst);
            if (!is_dir($d)) mkdir($d, 0777, true);
            copy($f->getPathname(), $dst);
            $n++;
        }
    }
    echo " → 已复制 {$n} 个文件到 {$dstUpload}\n";
} else {
    echo " → 目录不存在，跳过文件复制（图片地址已改写为 /upload/image/...，请自行补齐图片文件）\n";
}
}

// ---------- 7) 留言导入（可选 --with-message） ----------
if ($withMsg) {
    echo "\n【7/7】留言导入（--with-message）...\n";
    // 保证默认留言板存在
    $board = yone($e, "SELECT id FROM {$tablePrefix}message_board WHERE name='default'");
    if (!$board) {
        $t = time();
        yq($e, "INSERT INTO {$tablePrefix}message_board (name,title,description,fields,audit,captcha,is_open,create_time,update_time) VALUES ('default','在线留言','',NULL,1,1,1,?,?)", [$t, $t]);
        $board = ['id' => (int)$e->lastInsertId()];
    }
    $boardId = (int)$board['id'];
    $tblMsg = "{$srcPrefix}message";
    $msgNew = 0;
    if (yone($e, "SHOW TABLES LIKE '{$tblMsg}'")) {
        foreach (yall($y, "SELECT * FROM {$tblMsg} ORDER BY id") as $m) {
            $ok = ytry(function () use ($e, $tablePrefix, $boardId, $m, $tblMsg) {
                // 云优留言字段：title/name/tel/email/content/create_time/reply/status 等，按存在性适配
                $name    = (string)($m['name'] ?? ($m['username'] ?? ($m['title'] ?? '')));
                $contact = (string)($m['tel'] ?? ($m['phone'] ?? ''));
                $email   = (string)($m['email'] ?? '');
                $subject = (string)($m['subject'] ?? '');
                $content = norm_html((string)($m['content'] ?? ''));
                $reply   = norm_html((string)($m['reply'] ?? ''));
                $t = time();
                $status = 1; $isShow = 1;
                if (isset($m['status'])) { $status = (int)$m['status'] ? 1 : 0; }
                if (isset($m['is_show'])) { $isShow = (int)$m['is_show']; }
                yq($e, "INSERT INTO {$tablePrefix}message (board_id,name,contact,email,subject,content,reply,status,is_show,query_code,create_time,update_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$boardId, $name, $contact, $email, $subject, $content, $reply, $status, $isShow, 'Y' . substr(md5(uniqid('', true)), 0, 8), (int)($m['create_time'] ?? $t), (int)($m['update_time'] ?? $t)]);
                return true;
            }, '留言', (string)($m['id'] ?? ''));
            if ($ok) $msgNew++;
        }
    }
    echo "  留言导入完成：新增 {$msgNew}\n";
}

// ---------- 8) 写 id_map.json（云优栏目ID → 易云栏目ID） ----------
if ($catMap) {
    $mapPath = __DIR__ . DIRECTORY_SEPARATOR . 'id_map.json';
    file_put_contents($mapPath, json_encode($catMap, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo "\n栏目 ID 映射已写入：{$mapPath}\n";
}

// ---------- 汇总报告 ----------
echo "\n========== 导入完成 ==========\n";
echo "模型映射/新建：详见上方；栏目新增 {$catNew}；内容新增 {$conNew}（跳过 {$conSkip}）；块 {$blockNew}；广告 {$adNew}；友链 {$linkNew}" . ($withMsg ? "；留言 {$msgNew}" : '') . "\n";
if ($GLOBALS['__err']) {
    echo "\n! 共 " . count($GLOBALS['__err']) . " 条错误（已跳过不中断，请人工处理）：\n";
    foreach ($GLOBALS['__err'] as $er) echo "  {$er}\n";
} else {
    echo "\n✓ 无错误\n";
}
echo "\n下一步：清缓存（runtime/cache、runtime/index/temp）→ 后台「静态生成」→ 前台验证\n";
echo "提示：模板转换用 python tools/yunu/yunu_tpl_convert.py；完整指南见 tools/yunu/README.md\n";
