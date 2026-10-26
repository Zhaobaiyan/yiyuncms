<?php
// +----------------------------------------------------------------------
// | 前台首页控制器
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\common\service\TagService;

class Index extends FrontBase
{
    /**
     * 首页 / 引导页
     * 全局设置 base.splash=1 时：站点根 URL 显示引导页（模板 index_index.html），
     * 引导页内"进入网站"链接用 {yunu:url name='homepage' /} 进入正常首页（home()）。
     * base.splash=0（默认）时：站点根 URL 直接显示正常首页。
     */
    public function index()
    {
        // 站点关闭检测
        if (get_setting('site.close') == 1) {
            exit(get_setting('site.close_tip', '站点维护中，请稍后再访问'));
        }
        // 首页模板：优先 index_index.html，否则回退 index.html
        $langDir = lang_tpl_dir();
        $tpl = (is_file(root_path() . 'template' . DIRECTORY_SEPARATOR . $langDir . DIRECTORY_SEPARATOR . 'index_index.html'))
            ? 'index_index' : 'index';
        // 引导页开关：开启时根 URL 渲染引导页（同样用 index_index.html，模板内可放置"进入网站"入口）
        if (get_config('base.splash') == 1) {
            $tpl = 'index_index';
        }
        return view('/' . $tpl, ['cur_catid' => 0, 'cid' => 0, 'tip' => (string)input('get.tip', '')]);
    }

    /**
     * 正常首页（引导页开启时的"进入网站"目标页）
     * 模板：优先 index.html，否则回退 index_index.html
     */
    public function home()
    {
        // 站点关闭检测
        if (get_setting('site.close') == 1) {
            exit(get_setting('site.close_tip', '站点维护中，请稍后再访问'));
        }
        $langDir = lang_tpl_dir();
        $tpl = (is_file(root_path() . 'template' . DIRECTORY_SEPARATOR . $langDir . DIRECTORY_SEPARATOR . 'index.html'))
            ? 'index' : 'index_index';
        return view('/' . $tpl, ['cur_catid' => 0, 'cid' => 0, 'tip' => (string)input('get.tip', '')]);
    }
}
