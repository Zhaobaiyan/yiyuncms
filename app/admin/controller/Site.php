<?php
// +----------------------------------------------------------------------
// | 后台站群管理（v3.13 域名模式 / v3.18 路径模式）
// | 站点 = 独立入口 + 默认语言 + 语言白名单
// | 路径模式（v3.18）：本域名 + 路径别名（newcms.com/shenyang/），新建时自动：
// |   ① 注册语言版本（code=别名，模板文件夹=别名）
// |   ② 克隆源语言的整套数据表（含数据，独立 id 从 1 开始）
// |   ③ 复制源语言的模板文件夹（PC + wap）到 template/{别名}
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Lang;
use think\facade\Cache;
use think\facade\Db;

class Site extends AdminBase
{
    /**
     * 站点列表
     */
    public function index()
    {
        $rows = Db::name('site')->order('sort', 'asc')->order('id', 'asc')->select()->toArray();
        // 语言代码 → 名称映射（显示用）
        $langNames = [];
        try {
            $langNames = Lang::column('name', 'code');
        } catch (\Throwable $e) {
        }
        foreach ($rows as &$r) {
            $r['lang_names'] = [];
            $arr = json_decode((string)$r['langs'], true);
            if (is_array($arr)) {
                foreach ($arr as $c) {
                    $r['lang_names'][] = $c . (isset($langNames[$c]) ? '(' . $langNames[$c] . ')' : '');
                }
            }
            $r['default_lang_name'] = isset($langNames[$r['default_lang']]) ? $langNames[$r['default_lang']] : $r['default_lang'];
            // 路径模式：访问入口示例
            $r['entry'] = ($r['mode'] ?? 'domain') === 'path'
                ? '/' . ltrim((string)$r['alias'], '/') . '/'
                : ($r['domain'] ?: '');
        }
        unset($r);
        return view('site/index', [
            'rows'       => $rows,
            'site_multi' => (int)get_config('site.multi', 0),
        ]);
    }

    /**
     * 添加 / 编辑表单
     */
    public function edit()
    {
        $id   = (int)input('id', 0);
        $row  = $id ? Db::name('site')->where('id', $id)->find() : [
            'id' => 0, 'name' => '', 'domain' => '', 'alias' => '', 'mode' => 'path',
            'default_lang' => default_lang(), 'langs' => '', 'status' => 1, 'sort' => 0, 'remark' => '',
        ];
        if (!$row) return $this->fail('站点不存在');
        $row['langs'] = json_decode((string)$row['langs'], true) ?: [];
        $langs = Lang::where('is_open', 1)->order('sort', 'asc')->select();
        return view('site/edit', [
            'row'   => $row,
            'langs' => $langs,
        ]);
    }

    /**
     * 保存站点
     */
    public function save()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $id      = (int)input('post.id', 0);
        $name    = trim((string)input('post.name', ''));
        $mode    = trim((string)input('post.mode', 'path'));
        $domain  = strtolower(trim((string)input('post.domain', '')));
        $alias   = strtolower(trim((string)input('post.alias', '')));
        $defLang = trim((string)input('post.default_lang', ''));
        $langs   = input('post.langs/a', []);
        // 兼容 JSON 字符串提交（防御性）
        if (is_string($langs)) {
            $langs = json_decode($langs, true) ?: [];
        }
        $status     = (int)input('post.status', 0);
        $sort       = (int)input('post.sort', 0);
        $remark     = trim((string)input('post.remark', ''));
        $copySource = trim((string)input('post.copy_source', ''));
        $copyTpl    = (int)input('post.copy_tpl', 1);
        $copyData   = (int)input('post.copy_data', 1);

        if ($name === '') return $this->fail('请填写站点名称');
        if (!in_array($mode, ['path', 'domain'], true)) $mode = 'path';

        if ($mode === 'path') {
            // 编辑已有路径站点时：别名不可修改（该别名已注册为语言并克隆了数据表/模板），以库中原值为准
            if ($id) {
                $oldAlias = (string)Db::name('site')->where('id', $id)->value('alias');
                if ($oldAlias === '' || !preg_match('/^[a-z]{2,20}$/', $oldAlias)) {
                    return $this->fail('原站点别名异常，请联系管理员修复');
                }
                $alias = $oldAlias;
            }
            // 路径别名校验
            if (!preg_match('/^[a-z]{2,20}$/', $alias)) {
                return $this->fail('路径别名需为 2~20 位小写字母（如 shenyang、shanghai）');
            }
            $dup = Db::name('site')->where('alias', $alias)->where('id', '<>', $id)->find();
            if ($dup) return $this->fail('该路径别名已被站点「' . $dup['name'] . '」占用');
            // 语言代码重复校验：仅新建时执行（编辑时该别名已注册为本站点自身语言，属正常状态）
            if (!$id && Lang::where('code', $alias)->count()) {
                return $this->fail('该别名与语言代码「' . $alias . '」重复，请更换别名');
            }
            // 新建时：注册语言 + 克隆数据表 + 复制模板
            if (!$id) {
                // 复制源：默认语言
                if ($copySource === '') $copySource = default_lang();
                if (!Lang::where('code', $copySource)->count()) return $this->fail('复制源语言不存在');
                if ($copySource === $alias) return $this->fail('复制源不能是本站点自身语言');
                Lang::create([
                    'code' => $alias, 'name' => $name, 'tpl_dir' => $alias,
                    'flag' => '', 'is_default' => 0, 'is_open' => 1, 'sort' => 100,
                ]);
                try {
                    lang_clone_tables($alias, $copySource, (bool)$copyData);
                } catch (\Throwable $e) {
                    Lang::where('code', $alias)->delete();
                    $this->clearCache();
                    return $this->fail('数据库复制失败：' . $e->getMessage());
                }
                if ($copyTpl) {
                    $srcDir = lang_tpl_dir($copySource);
                    $dstDir = root_path() . 'template' . DIRECTORY_SEPARATOR . $alias;
                    if (!copy_dir(root_path() . 'template' . DIRECTORY_SEPARATOR . $srcDir, $dstDir)) {
                        // 模板复制失败不阻断（模板可稍后手动上传）
                        AdminLog::record('site_tpl_warn', '站群站点 ' . $name . ' 模板复制失败（源 template/' . $srcDir . '）');
                    }
                }
                AdminLog::record('site_add', '新增站群站点 ' . $name . '（路径 /' . $alias . '/，复制自 ' . $copySource . '）');
            }
            // 启用语言白名单：表单勾选 + 始终包含站点自身语言（编辑时保留勾选，不再强制覆盖）
            $langs = input('post.langs/a', []);
            if (is_string($langs)) {
                $langs = json_decode($langs, true) ?: [];
            }
            $langs = array_values(array_unique(array_merge([$alias], array_map('strval', $langs))));
            $data = [
                'name' => $name, 'mode' => 'path', 'alias' => $alias, 'domain' => '',
                'default_lang' => $alias, 'langs' => json_encode($langs, JSON_UNESCAPED_UNICODE),
                'status' => $status ? 1 : 0, 'sort' => $sort, 'remark' => $remark,
            ];
        } else {
            if ($domain === '') return $this->fail('请填写域名');
            if ($defLang === '') return $this->fail('请选择默认语言');
            // 域名重复校验（排除自身）
            $dup = Db::name('site')->where('domain', $domain)->where('id', '<>', $id)->find();
            if ($dup) return $this->fail('该域名已被站点「' . $dup['name'] . '」占用');
            // 白名单必须是已启用语言
            $global = Lang::where('is_open', 1)->column('code');
            $langs  = array_values(array_filter(array_map('strval', $langs), fn($v) => in_array($v, $global)));
            if ($langs && !in_array($defLang, $langs)) {
                $langs[] = $defLang;
            }
            $data = [
                'name' => $name, 'mode' => 'domain', 'alias' => '', 'domain' => $domain,
                'default_lang' => $defLang, 'langs' => json_encode($langs, JSON_UNESCAPED_UNICODE),
                'status' => $status ? 1 : 0, 'sort' => $sort, 'remark' => $remark,
            ];
        }

        if ($id) {
            Db::name('site')->where('id', $id)->update($data);
            AdminLog::record('site_edit', '编辑站群站点 #' . $id . ' ' . $name);
        } else {
            $data['create_time'] = time();
            Db::name('site')->insert($data);
        }
        $this->clearCache();
        return $this->ok('保存成功', '/admin/site/index');
    }

    /**
     * 复制模板文件夹到指定语言（站点/多语言共用）
     * POST: lang 目标语言代码、src 源语言代码
     */
    public function copyTpl()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $lang = trim((string)input('post.lang', ''));
        $src  = trim((string)input('post.src', ''));
        if (!Lang::where('code', $lang)->count()) return $this->fail('目标语言不存在');
        if (!Lang::where('code', $src)->count()) return $this->fail('源语言不存在');
        if ($lang === $src) return $this->fail('源与目标不能相同');
        $srcDir = lang_tpl_dir($src);
        $dstDir = root_path() . 'template' . DIRECTORY_SEPARATOR . lang_tpl_dir($lang);
        if (!is_dir(root_path() . 'template' . DIRECTORY_SEPARATOR . $srcDir)) {
            return $this->fail('源模板文件夹 template/' . $srcDir . ' 不存在');
        }
        $ok = copy_dir(root_path() . 'template' . DIRECTORY_SEPARATOR . $srcDir, $dstDir);
        if (!$ok) return $this->fail('模板复制失败（目标文件夹不可写？）');
        AdminLog::record('tpl_copy', '复制模板：' . $srcDir . ' → ' . lang_tpl_dir($lang));
        return $this->ok('模板已复制：template/' . $srcDir . ' → template/' . lang_tpl_dir($lang));
    }

    /**
     * 删除站点（路径模式站点同时停用其语言版本，数据表保留防误删）
     */
    public function delete()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $id = (int)input('post.id', 0);
        if (!$id) return $this->fail('参数错误');
        $row = Db::name('site')->where('id', $id)->find();
        if (!$row) return $this->fail('站点不存在');
        Db::name('site')->where('id', $id)->delete();
        // 路径模式站点：停用其同名语言版本（前台 /alias/ 立即失效；数据表保留）
        if (($row['mode'] ?? 'domain') === 'path' && $row['alias']) {
            Lang::where('code', $row['alias'])->update(['is_open' => 0]);
        }
        AdminLog::record('site_delete', '删除站群站点 #' . $id . ' ' . $row['name']);
        $this->clearCache();
        return $this->ok('已删除');
    }

    /**
     * 启停
     */
    public function status()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $id = (int)input('post.id', 0);
        $st = (int)input('post.status', 0);
        if (!$id) return $this->fail('参数错误');
        Db::name('site')->where('id', $id)->update(['status' => $st ? 1 : 0]);
        $this->clearCache();
        return $this->ok('已切换');
    }

    /**
     * 排序
     */
    public function sort()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $id  = (int)input('post.id', 0);
        $val = (int)input('post.val', 0);
        if (!$id) return $this->fail('参数错误');
        Db::name('site')->where('id', $id)->update(['sort' => $val]);
        $this->clearCache();
        return $this->ok('已保存');
    }

    /**
     * 站群/语言相关缓存
     */
    protected function clearCache()
    {
        Cache::delete('yy_site_hosts');
        Cache::delete('yy_site_paths');
        Cache::delete('yy_site_langs');
        Cache::delete('yy_site_default');
        Cache::delete('yy_lang_codes');
        Cache::delete('yy_default_lang');
        Cache::delete('yy_lang_tpldir');
        Cache::delete('yy_lang_appmap');
    }
}
