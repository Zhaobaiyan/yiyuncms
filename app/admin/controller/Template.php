<?php
// +----------------------------------------------------------------------
// | 模板管理控制器（自定义模板）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;

class Template extends AdminBase
{
    // 允许编辑的文件扩展名
    protected $allowExt = ['html', 'css', 'js', 'tpl', 'txt'];

    /**
     * 模板列表（列出各语言模板目录：PC + wap 手机版）
     */
    public function index()
    {
        $langs = \app\common\model\Lang::order('sort', 'asc')->select();
        $list  = [];
        foreach ($langs as $l) {
            $dir  = lang_tpl_dir($l['code']); // 模板文件夹名（可与语言代码不同）
            $base = root_path() . 'template' . DIRECTORY_SEPARATOR . $dir;
            $list[] = [
                'code'   => $l['code'],
                'name'   => $l['name'],
                'dir'    => $dir,
                'pc'     => is_dir($base),
                'wap'    => is_dir($base . DIRECTORY_SEPARATOR . 'wap'),
            ];
        }
        return view('template/index', ['tpls' => $list, 'admin_lang' => lang_code()]);
    }

    /**
     * 模板文件列表 + 在线编辑
     * @param string $tpl 语言代码
     */
    public function edit($tpl = '')
    {
        $tpl = $tpl ?: lang_code();
        $base = $this->safeTplDir($tpl);
        if (!$base) return $this->fail('该语言模板目录不存在（template/' . $tpl . '）');
        $file = input('get.file', '');
        if ($file !== '') {
            $filePath = $this->resolveFile($base, $file);
            if (!$filePath) return $this->fail('非法文件路径');

            if (request()->isPost()) {
                $content = input('post.content', '', null);
                file_put_contents($filePath, $content);
                AdminLog::record('template_edit', '编辑模板文件：' . $tpl . '/' . $file);
                return $this->ok('保存成功');
            }
            $fileInfo = ['name' => $file, 'content' => file_get_contents($filePath)];
            return view('template/file', ['tpl' => $tpl, 'fileInfo' => $fileInfo]);
        }

        // 文件树（按文件夹/类别分组，含最后修改时间）
        $groups = $this->scanFiles($base);
        return view('template/tree', ['tpl' => $tpl, 'groups' => $groups]);
    }

    /**
     * 校验模板目录合法
     */
    protected function safeTplDir($tpl)
    {
        $tpl = str_replace(['/', '\\', '..'], '', $tpl);
        $base = root_path() . 'template' . DIRECTORY_SEPARATOR . $tpl;
        return is_dir($base) ? $base : false;
    }

    /**
     * 解析并校验文件路径（防目录穿越）
     */
    protected function resolveFile($base, $file)
    {
        $file = str_replace('\\', '/', $file);
        if (strpos($file, '..') !== false) return false;
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), $this->allowExt)) return false;
        $real = realpath($base . DIRECTORY_SEPARATOR . $file);
        if (!$real || strpos($real, realpath($base)) !== 0) return false;
        return $real;
    }

    /**
     * 扫描模板文件，先分电脑端/手机版，再按类别（首页/列表/内容/样式/公共）分组
     * 每项带最后修改时间
     */
    protected function scanFiles($base)
    {
        $allow = $this->allowExt;
        $cate = function ($name) {
            if (strpos($name, 'index') !== false) return '首页模板';
            if (strpos($name, 'list') !== false)  return '列表模板';
            if (strpos($name, 'show') !== false)  return '内容模板';
            if (preg_match('/\.(css|js)$/i', $name)) return '样式/脚本';
            return '公共/其他';
        };
        $scanDir = function ($dir, $prefix) use ($allow, $cate) {
            $cats = [];
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*') as $p) {
                if (is_file($p) && in_array(strtolower(pathinfo($p, PATHINFO_EXTENSION)), $allow)) {
                    $cats[$cate(basename($p))][] = ['name' => $prefix . basename($p), 'mtime' => date('Y-m-d H:i', filemtime($p))];
                }
            }
            foreach (['css', 'js'] as $sd) {
                $sdir = $dir . DIRECTORY_SEPARATOR . $sd;
                if (is_dir($sdir)) {
                    foreach (glob($sdir . DIRECTORY_SEPARATOR . '*') as $p) {
                        if (is_file($p) && in_array(strtolower(pathinfo($p, PATHINFO_EXTENSION)), $allow)) {
                            $cats['样式/脚本'][] = ['name' => $prefix . $sd . '/' . basename($p), 'mtime' => date('Y-m-d H:i', filemtime($p))];
                        }
                    }
                }
            }
            $out = [];
            foreach ($cats as $k => $fs) { $out[] = ['name' => $k, 'files' => $fs]; }
            return $out;
        };
        $ends = [];
        $ends[] = ['name' => '电脑端 (PC)', 'groups' => $scanDir($base, '')];
        if (is_dir($base . DIRECTORY_SEPARATOR . 'wap')) {
            $ends[] = ['name' => '手机版 (WAP)', 'groups' => $scanDir($base . DIRECTORY_SEPARATOR . 'wap', 'wap/')];
        }
        return $ends;
    }
}
