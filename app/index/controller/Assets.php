<?php
// +----------------------------------------------------------------------
// | 前台模板资源控制器
// | 模板中 __PUBLIC__/xxx 指向本控制器（config/view.php tpl_replace_string）
// | 资源物理存放于 template/{当前语言模板目录}/（如 template/cn/css、template/cn/js、template/cn/images）
// | 多语言：优先读当前语言模板目录，文件不存在时回退主语言模板目录
// +----------------------------------------------------------------------
namespace app\index\controller;

class Assets extends FrontBase
{
    // 允许输出的扩展名（css/js/图片/字体）
    protected $allowExt = [
        'css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'bmp',
        'woff', 'woff2', 'ttf', 'eot', 'otf', 'map',
    ];

    protected $mimeMap = [
        'css' => 'text/css; charset=utf-8',
        'js'  => 'application/javascript; charset=utf-8',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon', 'bmp' => 'image/bmp',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject', 'otf' => 'font/otf',
        'map' => 'application/json; charset=utf-8',
    ];

    public function index()
    {
        // 资源路径来自 query 参数 path（模板 __PUBLIC__/xxx → /assets/index?path=/xxx）
        $path = ltrim((string)input('get.path', ''), '/');
        if ($path === '') {
            return abort(404, '资源不存在');
        }
        // 安全校验：禁止 .. 与反斜杠、必须白名单扩展名
        if (strpos($path, '..') !== false || strpos($path, '\\') !== false) {
            return abort(403, '非法路径');
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowExt, true)) {
            return abort(403, '不允许的资源类型');
        }
        // 当前语言模板目录优先，其次主语言模板目录
        $rel = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $dir = lang_tpl_dir();
        $file = root_path() . 'template' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $rel;
        if (!is_file($file)) {
            $mainDir = lang_tpl_dir(default_lang());
            if ($mainDir !== $dir) {
                $file = root_path() . 'template' . DIRECTORY_SEPARATOR . $mainDir . DIRECTORY_SEPARATOR . $rel;
            }
        }
        if (!is_file($file)) {
            return abort(404, '资源不存在');
        }
        // 输出
        header('Content-Type: ' . ($this->mimeMap[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        echo file_get_contents($file);
        exit;
    }
}
