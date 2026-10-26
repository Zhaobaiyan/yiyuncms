<?php
// +----------------------------------------------------------------------
// | 易云CMS 升级脚本：投稿审核机制（v3.5）
// | 用法：php upgrade_audit.php [部署.env路径]
// | 作用：
// |   1) 所有语言内容表（yy_*_content）新增 author_uid 发稿人 / audit_uid 审核人 / audit_time 审核时间
// |   2) 后台角色表 yy_admin_role 新增 need_audit（1=该角色发布内容默认草稿，需管理员/审核角色改为发布）
// |   3) 内置角色默认值：super/admin/message/seo 无需审核，tougao 投稿员需审核
// +----------------------------------------------------------------------
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

$envFile = isset($argv[1]) ? $argv[1] : '';
if (!$envFile || !is_file($envFile)) {
    $candidates = [
        'D:/phpstudy_pro/WWW/newcms/.env',
        __DIR__ . '/../.env',
    ];
    foreach ($candidates as $c) {
        if (is_file($c)) { $envFile = $c; break; }
    }
}
if (!$envFile || !is_file($envFile)) {
    fwrite(STDERR, "未找到 .env 配置文件，请传参：php upgrade_audit.php <部署 .env 路径>\n");
    exit(1);
}

$ini = parse_ini_file($envFile, true);
$db  = $ini['DATABASE'] ?? [];
$host = $db['HOSTNAME'] ?? '127.0.0.1';
$port = $db['HOSTPORT'] ?? '3306';
$name = $db['DATABASE'] ?? '';
$user = $db['USERNAME'] ?? 'root';
$pass = $db['PASSWORD'] ?? '';
$charset = $db['CHARSET'] ?? 'utf8mb4';

if (!$name) { fwrite(STDERR, ".env 缺少 DATABASE 配置\n"); exit(1); }

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset={$charset}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, '数据库连接失败：' . $e->getMessage() . "\n");
    exit(1);
}

function colExists(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
    $st->execute([$table, $col]);
    return (int)$st->fetchColumn() > 0;
}

function tableExists(PDO $pdo, string $table): bool
{
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
    $st->execute([$table]);
    return (int)$st->fetchColumn() > 0;
}

echo "=== 1) 语言内容表加列 ===\n";
$tables = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'yy\\_%\\_content'")->fetchAll();
$cols = [
    'author_uid' => "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '发稿人管理员ID'",
    'audit_uid'  => "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核人管理员ID'",
    'audit_time' => "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间'",
];
$cnt = 0;
foreach ($tables as $t) {
    $tbl = $t['TABLE_NAME'];
    foreach ($cols as $col => $def) {
        if (colExists($pdo, $tbl, $col)) { echo "  [skip] {$tbl}.{$col} 已存在\n"; continue; }
        $pdo->exec("ALTER TABLE `{$tbl}` ADD COLUMN `{$col}` {$def}");
        echo "  [ok] {$tbl} + {$col}\n";
        $cnt++;
    }
}

echo "=== 2) 角色表加 need_audit ===\n";
if (!tableExists($pdo, 'yy_admin_role')) {
    echo "  [skip] yy_admin_role 不存在\n";
} elseif (!colExists($pdo, 'yy_admin_role', 'need_audit')) {
    $pdo->exec("ALTER TABLE `yy_admin_role` ADD COLUMN `need_audit` TINYINT NOT NULL DEFAULT 0 COMMENT '1=发布内容需审核（默认草稿）'");
    echo "  [ok] yy_admin_role + need_audit\n";
} else {
    echo "  [skip] yy_admin_role.need_audit 已存在\n";
}

echo "=== 3) 内置角色默认值 ===\n";
if (tableExists($pdo, 'yy_admin_role')) {
    $defaults = ['super' => 0, 'admin' => 0, 'tougao' => 1, 'message' => 0, 'seo' => 0];
    foreach ($defaults as $code => $v) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM `yy_admin_role` WHERE `code`=?");
        $st->execute([$code]);
        if ((int)$st->fetchColumn() > 0) {
            $pdo->prepare("UPDATE `yy_admin_role` SET `need_audit`=? WHERE `code`=?")->execute([$v, $code]);
            echo "  [ok] {$code}.need_audit = {$v}\n";
        } else {
            echo "  [skip] 角色 {$code} 不存在（自定义安装）\n";
        }
    }
}

echo "\n升级完成：内容表新增列 {$cnt} 个。\n";
