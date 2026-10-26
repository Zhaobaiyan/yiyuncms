<?php
// +----------------------------------------------------------------------
// | 文件上传服务（共享层）
// | Upload 控制器（通用上传）与 Ueditor 控制器（富文本上传）共用：
// |   - store(): 校验 → 保存本地 /upload → 七牛开启时直传七牛
// |   - listMedia(): 扫描 /upload 目录返回图片/文件列表（在线管理/图片选择器）
// +----------------------------------------------------------------------
namespace app\common\service;

use think\file\UploadedFile;

class UploadService
{
    /**
     * 保存上传文件
     * @param UploadedFile $file    上传文件对象
     * @param int          $maxBytes 大小上限（字节）
     * @param array        $types    允许扩展名（小写，不带点）
     * @return array ['ok'=>bool, 'url'=>string, 'name'=>string, 'error'=>string]
     */
    public static function store(UploadedFile $file, int $maxBytes, array $types): array
    {
        $fileSize = $file->getSize();
        $fileExt  = strtolower($file->getOriginalExtension());
        if ($fileSize > $maxBytes) {
            return ['ok' => false, 'url' => '', 'name' => '', 'error' => '文件大小超出限制（' . round($maxBytes / 1048576, 1) . 'MB）'];
        }
        if (!in_array($fileExt, $types)) {
            return ['ok' => false, 'url' => '', 'name' => '', 'error' => '不允许的文件类型：' . $fileExt];
        }
        // 手动保存：TP6 File::move 底层用 rename()，在部分 Windows 环境会失败，改用 move_uploaded_file
        $sub   = date('Ymd');
        $dir   = public_path() . 'upload' . DIRECTORY_SEPARATOR . $sub;
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $name   = date('His') . '_' . uniqid() . '.' . $fileExt;
        $target = $dir . DIRECTORY_SEPARATOR . $name;
        if (!move_uploaded_file($file->getPathname(), $target)) {
            return ['ok' => false, 'url' => '', 'name' => '', 'error' => '文件保存失败'];
        }
        $saveName = $sub . '/' . $name;
        $url      = '/upload/' . $saveName;

        // 七牛开启：直传七牛，成功后删除本地临时文件
        if (Qiniu::enabled()) {
            $res = Qiniu::upload($target, 'uploads/' . $saveName);
            @unlink($target);
            if (!$res['ok']) {
                return ['ok' => false, 'url' => '', 'name' => '', 'error' => $res['error']];
            }
            $url = $res['url'];
        }
        return ['ok' => true, 'url' => $url, 'name' => $name, 'error' => ''];
    }

    /**
     * 扫描 /upload 目录的媒体文件（按修改时间倒序，取最近 limit 个）
     * @param string $kind  image=仅图片 / file=非图片文件 / all=全部
     * @param int    $limit 返回条数上限
     * @return array [['url'=>, 'time'=>], ...]
     */
    public static function listMedia(string $kind = 'image', int $limit = 50): array
    {
        $imgs  = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $out   = [];
        $dir   = public_path() . 'upload';
        if (!is_dir($dir)) return $out;
        self::scan($dir, $kind, $imgs, $out);
        usort($out, function ($a, $b) { return $b['time'] <=> $a['time']; });
        return array_slice($out, 0, $limit);
    }

    /**
     * 递归扫描目录
     */
    protected static function scan(string $dir, string $kind, array $imgs, array &$out): void
    {
        $items = @scandir($dir);
        if (!$items) return;
        foreach ($items as $it) {
            if ($it === '.' || $it === '..') continue;
            $p = $dir . DIRECTORY_SEPARATOR . $it;
            if (is_dir($p)) {
                self::scan($p, $kind, $imgs, $out);
            } else {
                $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
                $isImg = in_array($ext, $imgs);
                if (($kind === 'image' && $isImg) || ($kind === 'file' && !$isImg) || $kind === 'all') {
                    $out[] = [
                        'url'  => '/' . str_replace('\\', '/', str_replace(public_path(), '', $p)),
                        'time' => filemtime($p),
                    ];
                }
            }
        }
    }

    /**
     * 素材库全量扫描（v3.21）：扫描 /upload 目录全部媒体文件（含名称/大小/类型）
     * @param string $kind image=图片 / file=文件 / all=全部
     * @return array [['url'=>, 'name'=>, 'size'=>, 'time'=>, 'is_img'=>bool], ...] 按修改时间倒序
     */
    public static function mediaAll(string $kind = 'all'): array
    {
        $imgs = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $raw  = [];
        $dir  = public_path() . 'upload';
        if (is_dir($dir)) self::scan($dir, $kind, $imgs, $raw);
        $out = [];
        foreach ($raw as $r) {
            $ext = strtolower(pathinfo((string)$r['url'], PATHINFO_EXTENSION));
            $out[] = [
                'url'    => (string)$r['url'],
                'name'   => (string)basename((string)$r['url']),
                'size'   => (int)@filesize(public_path() . ltrim((string)$r['url'], '/')),
                'time'   => (int)$r['time'],
                'is_img' => in_array($ext, $imgs, true),
            ];
        }
        usort($out, function ($a, $b) { return $b['time'] <=> $a['time']; });
        return $out;
    }
}
