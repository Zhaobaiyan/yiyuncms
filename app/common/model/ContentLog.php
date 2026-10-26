<?php
// +----------------------------------------------------------------------
// | 内容版本历史模型（表 yy_{lang}_content_log）
// | 内容保存时自动快照，后台可查看与回滚
// +----------------------------------------------------------------------
namespace app\common\model;

class ContentLog extends LangModel
{
    protected $name = 'content_log';
    protected $autoWriteTimestamp = false;

    /**
     * 记录一次版本快照
     * @param int    $contentId
     * @param string $action  save/rollback/audit/delete
     * @param array  $data    快照字段（title/description/content/thumb）
     * @param string $log     备注
     */
    public static function record($contentId, $action, $data, $log = '')
    {
        // 每内容最多保留 30 条，超出删除最旧
        $cnt = static::where('content_id', (int)$contentId)->count();
        if ($cnt >= 30) {
            $old = static::where('content_id', (int)$contentId)->order('id', 'asc')->limit($cnt - 29)->column('id');
            if ($old) static::whereIn('id', $old)->delete();
        }
        return static::create([
            'content_id' => (int)$contentId,
            'admin_id'   => (int)(session('admin_id') ?: 0),
            'action'     => $action,
            'title'      => (string)($data['title'] ?? ''),
            'description'=> (string)($data['description'] ?? ''),
            'content'    => (string)($data['content'] ?? ''),
            'thumb'      => (string)($data['thumb'] ?? ''),
            'log'        => (string)$log,
            'create_time'=> time(),
        ]);
    }

    /**
     * 某内容的版本列表（新→旧）
     */
    public static function logs($contentId)
    {
        return static::where('content_id', (int)$contentId)
            ->order('id', 'desc')
            ->limit(30)
            ->select();
    }
}
