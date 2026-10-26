<?php
// +----------------------------------------------------------------------
// | 会员收藏模型（表 yy_{lang}_favorite）
// +----------------------------------------------------------------------
namespace app\common\model;

class Favorite extends LangModel
{
    protected $name = 'favorite';
    protected $autoWriteTimestamp = false;

    /**
     * 是否已收藏
     */
    public static function isFav($memberId, $contentId)
    {
        return (bool)static::where('member_id', (int)$memberId)
            ->where('content_id', (int)$contentId)->count();
    }

    /**
     * 收藏/取消收藏（返回是否已收藏）
     */
    public static function toggle($memberId, $contentId)
    {
        $row = static::where('member_id', (int)$memberId)
            ->where('content_id', (int)$contentId)->find();
        if ($row) {
            $row->delete();
            return false;
        }
        static::create(['member_id' => (int)$memberId, 'content_id' => (int)$contentId, 'create_time' => time()]);
        return true;
    }

    /**
     * 会员收藏列表（关联内容标题/链接）
     */
    public static function listByMember($memberId, $limit = 100)
    {
        return static::where('member_id', (int)$memberId)
            ->order('id', 'desc')
            ->limit($limit)
            ->select();
    }
}
