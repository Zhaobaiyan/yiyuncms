<?php
// +----------------------------------------------------------------------
// | 前台标签聚合页控制器（云优兼容：tag_index.html 模板）
// | URL: /tag/index?title=关键词  或  /tag?title=关键词
// | 输出 {yunu:list tag="$title"} 所需 $title 变量与分页
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\common\model\Category;
use app\common\model\Content;

class Tag extends FrontBase
{
    /**
     * 标签聚合页
     */
    public function index()
    {
        $title = trim(input('get.title', ''));
        $page  = max(1, (int)input('get.page', 1));

        $list  = null;
        $total = 0;
        if ($title !== '') {
            $list = Content::published()
                ->where(function ($q) use ($title) {
                    // tag 字段逗号/顿号/空格分隔，按关键词模糊匹配
                    $q->where('tag', 'like', '%' . $title . '%');
                })
                ->order('create_time', 'desc')
                ->paginate([
                    'list_rows' => content_pagesize(),
                    'page'      => $page,
                    'query'     => ['title' => $title],
                ]);
            $total = $list->total();
        }

        $langDir = lang_tpl_dir();
        $tpl = (is_file(root_path() . 'template' . DIRECTORY_SEPARATOR . $langDir . DIRECTORY_SEPARATOR . 'tag_index.html'))
            ? 'tag_index' : 'index';

        return view('/' . $tpl, [
            'title'     => $title,
            'tag'       => $title,
            'list'      => $list,
            'total'     => $total,
            'cats'      => Category::getTree(),
            'cur_catid' => 0,
            'cid'       => 0,
        ]);
    }
}
