<?php
// +----------------------------------------------------------------------
// | 评论模型（前台评论 + 后台审核，表 yy_{lang}_comment）
// +----------------------------------------------------------------------
namespace app\common\model;

class Comment extends LangModel
{
    protected $name = 'comment';
    protected $autoWriteTimestamp = 'int';

    /**
     * 某内容的已通过评论列表
     */
    public static function listByContent($contentId, $limit = 50)
    {
        return static::where('content_id', (int)$contentId)
            ->where('status', 1)
            ->order('id', 'asc')
            ->limit($limit)
            ->select();
    }

    /**
     * 未处理（待审）评论数
     */
    public static function pendingCount()
    {
        return static::where('status', 0)->count();
    }
}
