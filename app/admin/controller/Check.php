<?php
// +----------------------------------------------------------------------
// | 文件完整性检查控制器（v3.10）
// | 扫描 app/template 下 PHP/HTML 文件，检查常见安全风险（危险函数/后门特征）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use think\facade\Session;

class Check extends AdminBase
{
    /**
     * 危险特征库
     */
    protected function patterns()
    {
        return [
            'eval('            => 'eval 动态执行',
            'assert('          => 'assert 动态执行',
            'create_function'  => 'create_function 动态函数',
            'system('          => 'system 命令执行',
            'exec('            => 'exec 命令执行',
            'shell_exec'       => 'shell_exec 命令执行',
            'passthru('        => 'passthru 命令执行',
            'popen('           => 'popen 命令执行',
            'proc_open'        => 'proc_open 命令执行',
            'pcntl_exec'       => 'pcntl_exec 命令执行',
            'base64_decode('   => 'base64_decode 编码执行',
            '\\x65\\x76\\x61\\x6c' => 'eval 混淆变体',
        ];
    }

    public function index()
    {
        return view('check/index', ['patterns' => $this->patterns()]);
    }

    /**
     * 执行扫描
     */
    public function scan()
    {
        $hits = [];
        $scanned = 0;
        $dirs = [root_path() . 'app', root_path() . 'template'];
        $patterns = $this->patterns();

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                $ext = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));
                if (!in_array($ext, ['php', 'html'], true)) continue;
                $scanned++;
                $content = @file_get_contents($f->getPathname());
                if ($content === false || $content === '') continue;
                foreach ($patterns as $pat => $label) {
                    if (strpos($content, $pat) === false) continue;
                    // 白名单：ThinkPHP 模板引擎自身的 eval 渲染（think\template 内部）不属于风险
                    if ($pat === 'eval(' && strpos($content, 'class Template') !== false) continue;
                    $hits[] = [
                        'file'  => str_replace(root_path(), '', str_replace('\\', '/', $f->getPathname())),
                        'line'  => self::findLine($content, $pat),
                        'pat'   => $pat,
                        'label' => $label,
                    ];
                    break;
                }
            }
        }

        Session::set('file_check_hits', $hits);
        Session::set('file_check_scanned', $scanned);
        AdminLog::record('file_check', '文件完整性扫描：' . $scanned . ' 个文件，风险 ' . count($hits) . ' 处');
        return redirect((string)url('admin/check/report'));
    }

    /**
     * 扫描结果页
     */
    public function report()
    {
        $hits = Session::pull('file_check_hits') ?: [];
        $scanned = Session::pull('file_check_scanned') ?: 0;
        return view('check/report', ['hits' => $hits, 'scanned' => $scanned]);
    }

    protected static function findLine($content, $pat)
    {
        $lines = explode("\n", $content);
        foreach ($lines as $i => $l) {
            if (strpos($l, $pat) !== false) return $i + 1;
        }
        return 0;
    }
}
