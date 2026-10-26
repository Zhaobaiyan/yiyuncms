<?php
// +----------------------------------------------------------------------
// | 评论管理控制器（v3.10）
// | 前台内容详情页评论提交 → 待审（可选）→ 后台审核/回复/删除
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Comment as CommentModel;
use app\common\model\Content;

class Comment extends AdminBase
{
    public function index()
    {
        $status    = input('get.status', '');
        $keyword   = trim((string)input('get.keyword', ''));
        $contentId = (int)input('get.content_id', 0);
        $page      = max(1, (int)input('get.page', 1));
        $pageSize  = (int)input('get.page_size', 20);

        $query = CommentModel::withTrashed();
        if ($status !== '') $query->where('status', (int)$status);
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', '%' . $keyword . '%')
                  ->whereOr('content', 'like', '%' . $keyword . '%');
            });
        }
        if ($contentId > 0) $query->where('content_id', $contentId);

        $list = $query->order('id', 'desc')->paginate([
            'list_rows' => $pageSize,
            'page'      => $page,
            'query'     => ['status' => $status, 'keyword' => $keyword, 'content_id' => $contentId],
        ]);

        // 评论所属内容标题映射（语言表独立，手动关联）
        $contentMap = [];
        $cids = array_unique(array_column($list->items(), 'content_id'));
        if ($cids) {
            foreach (Content::whereIn('id', $cids)->column('title', 'id') as $k => $v) $contentMap[$k] = $v;
        }

        return view('comment/index', [
            'list'      => $list,
            'status'    => $status,
            'keyword'   => $keyword,
            'content_id'=> $contentId,
            'page_size' => $pageSize,
            'contentMap'=> $contentMap,
        ]);
    }

    /**
     * 审核通过 / 驳回删除（id 批量，status=1 通过，status=2 驳回隐藏）
     */
    public function audit()
    {
        $ids    = input('post.ids', 0);
        $ids    = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        $ids    = array_map('intval', $ids);
        $status = (int)input('post.status', 1);
        $n = CommentModel::whereIn('id', $ids)->update(['status' => $status]);
        AdminLog::record('comment_audit', ($status == 1 ? '通过' : '驳回') . '评论 ' . $n . ' 条');
        return $this->ok(($status == 1 ? '已通过' : '已驳回') . ' ' . $n . ' 条评论', (string)url('admin/comment/index'));
    }

    /**
     * 回复评论（保存到 reply 字段并自动通过）
     */
    public function reply($id = 0)
    {
        $reply = trim((string)input('post.reply', ''));
        if ($reply === '') return $this->fail('请输入回复内容');
        $row = CommentModel::find($id);
        if (!$row) return $this->fail('评论不存在');
        $row->save(['reply' => $reply, 'reply_time' => time(), 'status' => 1]);
        AdminLog::record('comment_reply', '回复评论#' . $id);
        return $this->ok('已回复', (string)url('admin/comment/index'));
    }

    /**
     * 彻底删除评论
     */
    public function delete($id = 0)
    {
        $row = CommentModel::withTrashed()->find($id);
        if ($row) $row->forceDelete();
        AdminLog::record('comment_delete', '删除评论#' . $id);
        return $this->ok('已删除', (string)url('admin/comment/index'));
    }

    /**
     * 评论数据源（前台 {cms:comment} 标签/详情页调用）
     */
    public static function listByContent($contentId, $limit = 50)
    {
        return CommentModel::listByContent($contentId, $limit);
    }
}
