<?php
// +----------------------------------------------------------------------
// | 易云CMS 升级脚本：审核驳回/修改重提闭环（v3.6）
// | 用法：php upgrade_audit_v2.php [部署.env路径]
// | 作用：
// |   1) 所有语言内容表（yy_*_content）新增 audit_status 审核状态 / submit_count 提交次数 / reject_reason 驳回原因
// |   2) 每个语言新增审核日志表 yy_{lang}_content_audit_log（记录每次提交/驳回/通过，含说明与原因）
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
    fwrite(STDERR, "未找到 .env 配置文件，请传参：php upgrade_audit_v2.php <部署 .env 路径>\n");
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

echo "=== 1) 语言内容表加审核字段 ===\n";
$tables = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'yy\\_%\\_content'")->fetchAll();
$cols = [
    'audit_status'  => "TINYINT NOT NULL DEFAULT 0 COMMENT '审核状态 0正常 1待审核 2已驳回'",
    'submit_count'  => "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '提交审核次数'",
    'reject_reason' => "TEXT NULL COMMENT '最新驳回原因'",
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

echo "=== 2) 各语言审核日志表 ===\n";
foreach ($tables as $t) {
    $tbl = $t['TABLE_NAME'];
    if (strpos($tbl, 'yy_') !== 0 || substr($tbl, -8) !== '_content') continue;
    $lang = substr($tbl, 3, -8); // yy_cn_content -> cn
    $logTbl = 'yy_' . $lang . '_content_audit_log';
    if (tableExists($pdo, $logTbl)) {
        echo "  [skip] {$logTbl} 已存在\n";
        continue;
    }
    $pdo->exec("CREATE TABLE `{$logTbl}` (
        `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `content_id`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '内容ID',
        `type`        TINYINT      NOT NULL DEFAULT 1 COMMENT '1=提交(含修改说明) 2=驳回(含原因) 3=审核通过',
        `uid`         INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作人管理员ID',
        `note`        TEXT         NULL COMMENT '修改说明/驳回原因',
        `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `idx_content` (`content_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='内容审核日志-{$lang}'");
    echo "  [ok] {$logTbl} 创建成功\n";
}

echo "\n升级完成：内容表新增列 {$cnt} 个，审核日志表已就绪。\n";
