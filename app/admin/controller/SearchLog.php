<?php
// +----------------------------------------------------------------------
// | 搜索日志控制器（v3.10）
// | 查看前台搜索词、结果数、来源 IP，用于热门搜索与搜索优化
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\SearchLog as SearchLogModel;

class SearchLog extends AdminBase
{
    public function index()
    {
        $keyword = trim((string)input('get.keyword', ''));
        $page    = max(1, (int)input('get.page', 1));
        $pageSize = (int)input('get.page_size', 30);

        $query = SearchLogModel::order('id', 'desc');
        if ($keyword !== '') {
            $query->where('keyword', 'like', '%' . $keyword . '%');
        }
        $list = $query->paginate([
            'list_rows' => $pageSize,
            'page'      => $page,
            'query'     => ['keyword' => $keyword],
        ]);

        // 热门搜索词 Top 20
        $hot = SearchLogModel::hot(20);

        return view('search_log/index', [
            'list'     => $list,
            'keyword'  => $keyword,
            'page_size'=> $pageSize,
            'hot'      => $hot,
        ]);
    }

    public function clear()
    {
        SearchLogModel::where('id', '>', 0)->delete();
        return $this->ok('搜索日志已清空', (string)url('admin/search_log/index'));
    }
}
