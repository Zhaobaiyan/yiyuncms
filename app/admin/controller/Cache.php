<?php
// +----------------------------------------------------------------------
// | 缓存管理控制器（v3.10）
// | 查看缓存配置状态 + 一键清理模板/数据缓存
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use think\facade\Cache as CacheFacade;

class Cache extends AdminBase
{
    public function index()
    {
        // 当前缓存驱动与状态
        $driver = config('cache.default');
        $stores = config('cache.stores');
        $info = [
            'driver' => $driver,
            'type'   => $stores[$driver]['type'] ?? $driver,
            'prefix' => $stores[$driver]['prefix'] ?? '',
            'path'   => $stores[$driver]['path'] ?? (runtime_path() . 'cache'),
        ];
        // 缓存目录大小
        $cacheDir = runtime_path() . 'cache';
        $tempDir  = runtime_path() . 'temp';
        return view('cache/index', [
            'info'    => $info,
            'cacheMB' => self::dirSize($cacheDir),
            'tempMB'  => self::dirSize($tempDir),
        ]);
    }

    /**
     * 清理缓存（模板编译缓存 temp/ + 数据缓存 cache/）
     */
    public function clear()
    {
        $types = input('post.types', 'all');
        $done = [];
        if ($types === 'all' || $types === 'template') {
            self::clearDir(runtime_path() . 'temp');
            $done[] = '模板缓存';
        }
        if ($types === 'all' || $types === 'data') {
            try { CacheFacade::clear(); } catch (\Throwable $e) {}
            self::clearDir(runtime_path() . 'cache');
            $done[] = '数据缓存';
        }
        AdminLog::record('cache_clear', '清理缓存：' . implode('、', $done));
        return $this->ok('已清理：' . implode('、', $done), (string)url('admin/cache/index'));
    }

    protected static function dirSize($dir)
    {
        if (!is_dir($dir)) return 0;
        $size = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile()) $size += $f->getSize();
        }
        return round($size / 1048576, 2);
    }

    protected static function clearDir($dir)
    {
        if (!is_dir($dir)) return;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            if ($f->isDir()) @rmdir($f->getPathname());
            else @unlink($f->getPathname());
        }
    }
}
