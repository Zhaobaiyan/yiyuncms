<?php
// +----------------------------------------------------------------------
// | 静态页生成控制器（多语言）
// | 注：类名用 StaticPage（Static 是 PHP 保留字），URL 为 /admin/static_page/index
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Category;
use app\common\service\StaticPage as StaticPageService;

class StaticPage extends AdminBase
{
    /**
     * 静态页生成首页
     */
    public function index()
    {
        $mode = get_config('base.static_mode', 0);
        $cats = Category::getFlatList();
        $langs = \app\common\model\Lang::order('sort', 'asc')->select();
        return view('staticpage/index', ['mode' => $mode, 'cats' => $cats, 'langs' => $langs]);
    }

    /**
     * 生成首页（当前语言）
     */
    public function home()
    {
        $path = StaticPageService::home();
        AdminLog::record('static_home', '生成首页静态页[' . lang_code() . ']');
        return $this->ok('首页生成成功：' . $path);
    }

    /**
     * 生成指定栏目（当前语言）
     */
    public function category()
    {
        $id   = input('post.id', 0);
        $path = StaticPageService::category($id);
        if ($path === false) return $this->fail('生成失败，请检查栏目');
        AdminLog::record('static_category', '生成栏目静态页：' . $id);
        return $this->ok('栏目生成成功：' . $path);
    }

    /**
     * 生成栏目下全部内容（当前语言）
     */
    public function categoryContent()
    {
        $id    = input('post.id', 0);
        $n     = StaticPageService::categoryContent($id);
        AdminLog::record('static_content', '生成栏目内容静态页：' . $id . ' 共 ' . $n . ' 篇');
        return $this->ok('已生成 ' . $n . ' 篇内容静态页');
    }

    /**
     * 一键生成全站（可指定语言，或全部语言）
     */
    public function all()
    {
        $target = input('post.lang', lang_code());
        if ($target === 'all') {
            $count = StaticPageService::all();
            AdminLog::record('static_all', '一键生成全站静态页（全部语言）');
            return $this->ok('全部语言全站生成完成，共生成 ' . $count . ' 个页面');
        }
        if (!in_array($target, lang_codes())) return $this->fail('语言不存在');
        $count = StaticPageService::buildLang($target);
        AdminLog::record('static_all', '一键生成全站静态页[' . $target . ']');
        return $this->ok('[' . $target . '] 全站生成完成，共 ' . $count . ' 个页面');
    }

    /**
     * 开关静态模式（全局）
     */
    public function mode()
    {
        $val = intval(input('post.val', 0));
        set_config('base.static_mode', $val);
        AdminLog::record('static_mode', '静态模式：' . ($val ? '开启' : '关闭'));
        return $this->ok('已' . ($val ? '开启' : '关闭') . '静态模式');
    }
}
