<?php
// +----------------------------------------------------------------------
// | 语言版本管理控制器
// | 后台自由添加/编辑/删除语言版本，前台模板文件夹(tpl_dir)可单独绑定
// | 新增语言时：为 yy_{code}_* 建表（克隆默认语言结构）并复制默认设置，
// |             之后即可在后台切换录入、前台按 /{code}/ 访问
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Lang;
use think\facade\Cache;

class Language extends AdminBase
{
    // 语言相关数据表实体（与 install.sql 对应）
    protected $entities = ['category', 'model', 'model_field', 'content', 'message', 'message_board', 'block', 'ad', 'link', 'setting'];

    /**
     * 语言列表
     */
    public function index()
    {
        $langs = Lang::order('sort', 'asc')->order('id', 'asc')->select();
        return view('language/index', ['langs' => $langs]);
    }

    /**
     * 添加语言
     */
    public function add()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $code   = strtolower(trim(input('post.code', '')));
        $name   = trim(input('post.name', ''));
        $tplDir = trim(input('post.tpl_dir', ''));
        $flag   = trim(input('post.flag', ''));
        $sort   = (int)input('post.sort', 100);

        if (!preg_match('/^[a-z]{2,10}$/', $code)) {
            return $this->fail('语言代码需为 2~10 位小写字母（如 en、ru）');
        }
        if (in_array($code, \app\common\Service\LangAppService::RESERVED)) {
            return $this->fail('该代码为系统保留名（admin/index/common），请更换');
        }
        if ($name === '') return $this->fail('请填写语言名称');
        if (Lang::where('code', $code)->count()) {
            return $this->fail('该语言代码已存在');
        }
        if ($tplDir === '') $tplDir = $code;
        if (!preg_match('/^[a-zA-Z0-9_\-]{1,30}$/', $tplDir)) {
            return $this->fail('模板文件夹名不合法');
        }

        $defaultLang = default_lang();
        Lang::create([
            'code' => $code, 'name' => $name, 'tpl_dir' => $tplDir,
            'flag' => $flag, 'is_default' => 0, 'is_open' => 1, 'sort' => $sort,
        ]);
        // 为新增语言建表（克隆默认语言结构）并复制默认设置 / 默认留言板
        try {
            lang_clone_tables($code, $defaultLang, false);
        } catch (\Throwable $e) {
            Lang::where('code', $code)->delete();
            $this->clearCache();
            return $this->fail('数据表创建失败：' . $e->getMessage());
        }
        $this->clearCache();

        AdminLog::record('language_add', '新增语言：' . $name . '(' . $code . ') 模板文件夹 ' . $tplDir);
        return $this->ok('语言已添加，数据表已自动创建，请到「模板管理」确认/上传 ' . $tplDir . ' 模板，或在语言列表点「复制模板」从现有语言/站点复制', (string)url('admin/language/index'));
    }

    /**
     * 编辑语言
     */
    public function edit($id = 0)
    {
        $id  = (int)$id;
        $row = Lang::find($id);
        if (!$row) return $this->fail('语言不存在');

        if (request()->isPost()) {
            $name   = trim(input('post.name', ''));
            $tplDir = trim(input('post.tpl_dir', ''));
            $flag   = trim(input('post.flag', ''));
            $sort   = (int)input('post.sort', 100);
            if ($name === '') return $this->fail('请填写语言名称');
            if ($tplDir === '') $tplDir = $row['code'];
            if (!preg_match('/^[a-zA-Z0-9_\-]{1,30}$/', $tplDir)) {
                return $this->fail('模板文件夹名不合法');
            }
            $row->save(['name' => $name, 'tpl_dir' => $tplDir, 'flag' => $flag, 'sort' => $sort]);
            $this->clearCache();
            AdminLog::record('language_edit', '编辑语言：' . $name . '(' . $row['code'] . ')');
            return $this->ok('保存成功', (string)url('admin/language/index'));
        }

        return view('language/edit', ['info' => $row]);
    }

    /**
     * 删除语言（不自动删除其数据表，避免误删数据）
     */
    public function delete($id = 0)
    {
        $id  = (int)$id;
        $row = Lang::find($id);
        if (!$row) return $this->fail('语言不存在');
        if ($row['is_default'] == 1) return $this->fail('默认语言不可删除，请先更换默认语言');
        if (count(lang_codes()) <= 1) return $this->fail('至少保留一种已启用语言');
        Lang::where('code', $row['code'])->delete();
        $this->clearCache();
        AdminLog::record('language_delete', '删除语言：' . $row['name'] . '(' . $row['code'] . ')');
        return $this->ok('已删除语言「' . $row['name'] . '」。其数据表 yy_' . $row['code'] . '_* 已保留，如需彻底移除请手动删除。', (string)url('admin/language/index'));
    }

    /**
     * 设为默认语言
     */
    public function setDefault($id = 0)
    {
        $id  = (int)$id;
        $row = Lang::find($id);
        if (!$row) return $this->fail('语言不存在');
        if ($row['is_open'] != 1) return $this->fail('请先启用该语言再设为默认');
        Lang::where('1=1')->update(['is_default' => 0]);
        Lang::where('id', $id)->update(['is_default' => 1]);
        // 默认语言变更，若当前后台录入语言非默认则切过去
        session('admin_lang', $row['code']);
        // 同步伪静态：把 nginx.htaccess / .htaccess 的主语言代码改为新默认语言
        sync_pretty_firewall($row['code']);
        $this->clearCache();
        AdminLog::record('language_default', '设默认语言：' . $row['name'] . '(' . $row['code'] . ')');
        return $this->ok('已设为默认语言：' . $row['name'], (string)url('admin/language/index'));
    }

    /**
     * 启用 / 停用
     */
    public function setOpen($id = 0)
    {
        $id  = (int)$id;
        $row = Lang::find($id);
        if (!$row) return $this->fail('语言不存在');
        $open = (int)input('post.open', 0);
        if ($open === 0 && $row['is_default'] == 1) {
            return $this->fail('默认语言不可停用，请先更换默认语言');
        }
        if ($open === 0 && count(lang_codes()) <= 1) {
            return $this->fail('至少保留一种已启用语言');
        }
        $row->save(['is_open' => $open]);
        $this->clearCache();
        AdminLog::record('language_open', ($open ? '启用' : '停用') . '语言：' . $row['name']);
        return $this->ok('已' . ($open ? '启用' : '停用'));
    }

    /**
     * 排序
     */
    public function sort()
    {
        $ids = input('post.sort/a', []);
        foreach ($ids as $id => $sort) {
            Lang::where('id', $id)->update(['sort' => (int)$sort]);
        }
        $this->clearCache();
        return $this->ok('排序已保存');
    }

    /**
     * 复制模板文件夹到该语言（从任意现有语言/站点复制，v3.18）
     * POST: src 源语言代码
     */
    public function copyTpl($id = 0)
    {
        $id  = (int)$id;
        $row = Lang::find($id);
        if (!$row) return $this->fail('语言不存在');
        if (!request()->isPost()) return $this->fail('非法请求');
        $src = trim((string)input('post.src', ''));
        if ($src === '' || !Lang::where('code', $src)->count()) return $this->fail('请选择有效的源语言');
        if ($src === $row['code']) return $this->fail('源与目标不能相同');
        $srcDir = lang_tpl_dir($src);
        $dstDir = root_path() . 'template' . DIRECTORY_SEPARATOR . lang_tpl_dir($row['code']);
        if (!is_dir(root_path() . 'template' . DIRECTORY_SEPARATOR . $srcDir)) {
            return $this->fail('源模板文件夹 template/' . $srcDir . ' 不存在');
        }
        if (!copy_dir(root_path() . 'template' . DIRECTORY_SEPARATOR . $srcDir, $dstDir)) {
            return $this->fail('模板复制失败（目标文件夹不可写？）');
        }
        AdminLog::record('tpl_copy', '复制模板：' . $srcDir . ' → ' . lang_tpl_dir($row['code']));
        return $this->ok('模板已复制：template/' . $srcDir . ' → template/' . lang_tpl_dir($row['code']));
    }

    /**
     * 为新增语言创建数据表（克隆默认语言结构）并复制默认设置
     * 表名使用配置中的前缀：{prefix}{code}_{entity}
     * @deprecated v3.18 起改用公共函数 lang_clone_tables()
     */
    protected function createLangTables($code, $srcLang)
    {
        lang_clone_tables($code, $srcLang, false);
    }

    /**
     * 语言相关缓存
     */
    protected function clearCache()
    {
        Cache::delete('yy_lang_codes');
        Cache::delete('yy_default_lang');
        Cache::delete('yy_lang_tpldir');
        Cache::delete('yy_lang_appmap');
    }
}
