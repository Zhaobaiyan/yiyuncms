<?php
// v3.24 通知中心：数据库升级（幂等）
use think\facade\Db;
require __DIR__ . '/../vendor/autoload.php';
$app = new \think\App();
$app->initialize();

function colExists($table, $col) {
    foreach (Db::query("SHOW COLUMNS FROM $table") as $c) {
        if ($c['Field'] === $col) return true;
    }
    return false;
}

// 1. 客服会话/消息加客户 IP
foreach (['yy_service_session' => [['client_ip', "varchar(50) NOT NULL DEFAULT ''"]],
          'yy_service_msg' => [['client_ip', "varchar(50) NOT NULL DEFAULT ''"]]] as $t => $cols) {
    foreach ($cols as [$name, $def]) {
        if (!colExists($t, $name)) {
            Db::execute("ALTER TABLE $t ADD $name $def");
            echo "ADD $t.$name OK\n";
        } else {
            echo "SKIP $t.$name (exists)\n";
        }
    }
}

// 2. 管理员通知设置
$adminCols = [
    ['notify_service',   "tinyint(1) NOT NULL DEFAULT 1"],
    ['notify_guestbook', "tinyint(1) NOT NULL DEFAULT 0"],
    ['notify_email',     "varchar(100) NOT NULL DEFAULT ''"],
    ['notify_phone',     "varchar(30) NOT NULL DEFAULT ''"],
    ['wxwork_webhook',   "varchar(255) NOT NULL DEFAULT ''"],
];
foreach ($adminCols as [$name, $def]) {
    if (!colExists('yy_admin', $name)) {
        Db::execute("ALTER TABLE yy_admin ADD $name $def");
        echo "ADD yy_admin.$name OK\n";
    } else {
        echo "SKIP yy_admin.$name (exists)\n";
    }
}
echo 'DONE' . PHP_EOL;
