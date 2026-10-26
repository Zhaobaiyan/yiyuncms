<?php
// +----------------------------------------------------------------------
// | 前台搜索控制器
// | 支持：全站 / 指定栏目搜索，标题 / 正文 / 标题+正文 搜索，分页
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\common\model\Category;
use app\common\model\Content;

class Search extends FrontBase
{
    /**
     * 搜索页
     */
    public function index()
    {
        $kw    = trim(input('get.kw', ''));
        $catid = (int)input('get.catid', 0);
        $field = in_array(input('get.field', 'all'), ['all', 'title', 'content']) ? input('get.field', 'all') : 'all';
        $page  = max(1, (int)input('get.page', 1));

        $cats = Category::getTree();

        $list  = null;
        $total = 0;
        if ($kw !== '') {
            $q = Content::published();
            if ($catid > 0) {
                $ids = Category::getChildIds($catid);
                $q->whereIn('catid', $ids);
            }
            if ($field === 'title') {
                $q->where('title', 'like', '%' . $kw . '%');
            } elseif ($field === 'content') {
                $q->where('content', 'like', '%' . $kw . '%');
            } else {
                $q->where(function ($qq) use ($kw) {
                    $qq->where('title', 'like', '%' . $kw . '%')->whereOr('content', 'like', '%' . $kw . '%');
                });
            }
            $list = $q->order('create_time', 'desc')
                ->paginate([
                    'list_rows' => content_pagesize(),
                    'page'      => $page,
                    'query'     => ['kw' => $kw, 'catid' => $catid, 'field' => $field],
                ]);
            $total = $list->total();
            // 搜索日志（v3.10）：供后台热门搜索/搜索词排行
            try { \app\common\model\SearchLog::record($kw, $total); } catch (\Throwable $e) {}
        }

        // 云优模板兼容：搜索模板优先 search_index.html（否则 search.html）
        $langDir = lang_tpl_dir();
        $tpl = (is_file(root_path() . 'template' . DIRECTORY_SEPARATOR . $langDir . DIRECTORY_SEPARATOR . 'search_index.html'))
            ? 'search_index' : 'search';
        return view('/' . $tpl, [
            'kw'       => $kw,
            'keyword'  => $kw,
            'catid'    => $catid,
            'field'    => $field,
            'list'     => $list,
            'total'    => $total,
            'cats'     => $cats,
            'cur_catid' => 0,
            'cid'      => 0,
        ]);
    }
}
