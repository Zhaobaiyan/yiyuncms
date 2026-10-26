<?php
// +----------------------------------------------------------------------
// | 前台正常首页控制器（引导页"进入网站"目标页）
// | 访问 /home 渲染正常首页（模板 index.html），配合引导页开关 base.splash 使用
// +----------------------------------------------------------------------
namespace app\index\controller;

class Home extends FrontBase
{
    /**
     * 正常首页：优先 index.html，否则回退 index_index.html
     */
    public function index()
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
