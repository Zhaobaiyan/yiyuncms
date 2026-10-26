<?php
// +----------------------------------------------------------------------
// | 内置服务器路由脚本（php think run 使用）
// | 静态文件直接返回，动态请求转发到 index.php
// +----------------------------------------------------------------------
if (is_file($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"])) {
    return false;
} else {
    $_SERVER["SCRIPT_FILENAME"] = __DIR__ . '/index.php';
    require __DIR__ . "/index.php";
}
