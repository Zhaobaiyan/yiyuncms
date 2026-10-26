<?php
// +----------------------------------------------------------------------
// | 数据备份与恢复控制器（v3.10）
// | 纯 PHP 逐表导出 SQL（不依赖 mysqldump），备份文件保存到 runtime/backup/
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use think\facade\Db;

class Backup extends AdminBase
{
    protected function dir()
    {
        $d = runtime_path() . 'backup' . DIRECTORY_SEPARATOR;
        if (!is_dir($d)) @mkdir($d, 0755, true);
        return $d;
    }

    public function index()
    {
        // 已有备份文件列表
        $files = [];
        foreach (glob($this->dir() . '*.sql') ?: [] as $f) {
            $files[] = [
                'name' => basename($f),
                'size' => filesize($f),
                'time' => filemtime($f),
            ];
        }
        rsort($files, SORT_REGULAR);
        // 数据表清单（当前语言组：所有 yy_ 前缀表）
        $tables = [];
        foreach (Db::getTables() as $t) {
            if (strpos($t, 'yy_') === 0 || $t === 'yy_lang' || $t === 'yy_config') {
                $tables[] = ['name' => $t, 'count' => (int)Db::table($t)->count()];
            }
        }
        return view('backup/index', ['files' => $files, 'tables' => $tables]);
    }

    /**
     * 执行备份：导出全部 yy_* 表到 SQL 文件
     */
    public function backup()
    {
        $prefix = 'yy_';
        $tables = [];
        foreach (Db::getTables() as $t) {
            if (strpos($t, $prefix) === 0) $tables[] = $t;
        }
        if (!$tables) return $this->fail('未找到可备份的数据表');

        $sql = "-- 易云CMS 数据备份\n-- 时间：" . date('Y-m-d H:i:s') . "\nSET NAMES utf8mb4;\n\n";
        foreach ($tables as $t) {
            // 结构
            $create = Db::query('SHOW CREATE TABLE `' . $t . '`');
            if (!$create) continue;
            $sql .= "DROP TABLE IF EXISTS `" . $t . "`;\n" . $create[0]['Create Table'] . ";\n\n";
            // 数据
            $rows = Db::table($t)->select();
            foreach ($rows as $row) {
                $cols = array_map(function ($c) { return '`' . $c . '`'; }, array_keys($row));
                $vals = array_map(function ($v) {
                    if ($v === null) return 'NULL';
                    return "'" . addslashes((string)$v) . "'";
                }, array_values($row));
                $sql .= "INSERT INTO `" . $t . "` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
            }
            $sql .= "\n";
        }

        $name = 'backup_' . date('Ymd_His') . '.sql';
        $file = $this->dir() . $name;
        if (file_put_contents($file, $sql, LOCK_EX) === false) {
            return $this->fail('备份文件写入失败，请检查 runtime 目录权限');
        }
        AdminLog::record('backup', '数据备份：' . $name . '（' . count($tables) . ' 张表）');
        return $this->ok('备份成功：' . $name . '（' . count($tables) . ' 张表，' . round(filesize($file) / 1024) . ' KB）', (string)url('admin/backup/index'));
    }

    /**
     * 恢复备份（危险操作：将覆盖当前数据库）
     */
    public function restore()
    {
        $name = trim((string)input('post.file', ''));
        $file = $this->dir() . basename($name);
        if (!is_file($file) || pathinfo($file, PATHINFO_EXTENSION) !== 'sql') {
            return $this->fail('备份文件不存在');
        }
        $sql = file_get_contents($file);
        if ($sql === false || trim($sql) === '') return $this->fail('备份文件为空或不可读');

        // 分句执行（按 ; 分割，忽略注释行）
        $statements = array_filter(array_map('trim', explode(';', $sql)), function ($s) {
            return $s !== '' && strpos($s, '--') !== 0;
        });
        $done = 0;
        foreach ($statements as $stmt) {
            try {
                Db::execute($stmt);
                $done++;
            } catch (\Throwable $e) {
                // 单句失败继续（如重复索引），记录但不中断
            }
        }
        AdminLog::record('backup_restore', '恢复备份：' . $name . '（执行 ' . $done . ' 条语句）');
        return $this->ok('恢复完成：执行 ' . $done . ' 条语句', (string)url('admin/backup/index'));
    }

    /**
     * 下载备份文件
     */
    public function download()
    {
        $name = trim((string)input('get.file', ''));
        $file = $this->dir() . basename($name);
        if (!is_file($file)) abort(404, '文件不存在');
        return download($file, basename($file));
    }

    /**
     * 删除备份文件
     */
    public function delete()
    {
        $name = trim((string)input('post.file', ''));
        $file = $this->dir() . basename($name);
        if (is_file($file)) @unlink($file);
        return $this->ok('已删除', (string)url('admin/backup/index'));
    }
}
