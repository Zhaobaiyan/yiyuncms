<?php
// +----------------------------------------------------------------------
// | 云优CMS 栏目 ID 映射生成器
// | 作用：生成 tools/yunu/id_map.json（云优 category.id → 易云 category.id）
// |       供模板转换器 yunu_tpl_convert.py 把模板里 cid="2"/typeid="7" 等数字
// |       替换为易云真实栏目 ID。
// | 用法（在项目根目录执行）：
// |   php tools/yunu/gen_id_map.php
// |   php tools/yunu/gen_id_map.php --yunu-db=chty --yunu-user=root --yunu-pass=xxx
// |   php tools/yunu/gen_id_map.php --lang=en
// | 匹配规则：云优 etitle == 易云 ename（导入时原样保留），不匹配的栏目会列出并跳过。
// +----------------------------------------------------------------------
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
date_default_timezone_set('Asia/Shanghai');

// ---------- 参数 ----------
$args = getopt('', ['lang::', 'yunu-db::', 'yunu-user::', 'yunu-pass::', 'yunu-host::', 'help']);
$lang     = $args['lang'] ?? 'cn';
$yunuDb   = $args['yunu-db'] ?? 'chty';
$yunuUser = $args['yunu-user'] ?? 'root';
$yunuPass = $args['yunu-pass'] ?? '19990903';
$yunuHost = $args['yunu-host'] ?? '127.0.0.1';

if (isset($args['help'])) {
    echo "云优栏目 ID 映射生成器\nphp tools/yunu/gen_id_map.php [--lang=cn|en|...] [--yunu-db=chty] [--yunu-user=root] [--yunu-pass=xxx] [--yunu-host=127.0.0.1]\n";
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

// ---------- 连接 ----------
try {
    $y = new PDO("mysql:host={$yunuHost};dbname={$yunuDb};charset=utf8mb4", $yunuUser, $yunuPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $e = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $ex) {
    fwrite(STDERR, "连接失败：{$ex->getMessage()}\n");
    exit(1);
}
echo "已连接：云优库 {$yunuDb} → 易云库 {$dbName}（语言 {$lang}）\n";

// ---------- 读取两库栏目 ----------
$yu = $y->query("SELECT id, etitle FROM yunu_category")->fetchAll(PDO::FETCH_ASSOC);
$yi = $e->query("SELECT id, ename FROM {$tablePrefix}category")->fetchAll(PDO::FETCH_ASSOC);

// 双方统一取叶子名匹配（云优 etitle 是完整路径，易云 ename 是叶子名）
function leaf($name) {
    $name = trim((string)$name);
    return strpos($name, '/') !== false ? substr($name, strrpos($name, '/') + 1) : $name;
}
$yiByEname = [];
foreach ($yi as $r) {
    $ename = leaf($r['ename']);
    if ($ename !== '' && !isset($yiByEname[$ename])) {
        $yiByEname[$ename] = (int)$r['id'];
    }
}

$map = [];
$unmatched = [];
foreach ($yu as $r) {
    $yid = (int)$r['id'];
    $en  = leaf($r['etitle']);
    if (isset($yiByEname[$en])) {
        $map[$yid] = $yiByEname[$en];
    } else {
        $unmatched[] = ['id' => $yid, 'etitle' => $en];
    }
}

// ---------- 输出 ----------
$out = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'yunu' . DIRECTORY_SEPARATOR . 'id_map.json';
file_put_contents($out, json_encode($map, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "映射写入：{$out}\n";
echo "匹配栏目：" . count($map) . " 条 / 云优共 " . count($yu) . " 条\n";
if ($unmatched) {
    echo "未匹配（按 ename 找不到对应易云栏目，模板中这些 ID 将保持原值）：\n";
    foreach ($unmatched as $u) {
        echo "  云优 id={$u['id']} etitle={$u['etitle']}\n";
    }
}
