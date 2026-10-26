<?php
// +----------------------------------------------------------------------
// | 内容审核日志模型（投稿审核机制）
// | 表：yy_{lang}_content_audit_log（type: 1提交/2驳回/3通过）
// +----------------------------------------------------------------------
namespace app\common\model;

class ContentAuditLog extends LangModel
{
    protected $name = 'content_audit_log';
    protected $autoWriteTimestamp = 'int';

    /**
     * 记录一条审核日志
     * @param int    $contentId 内容ID
     * @param int    $type      1提交 2驳回 3通过
     * @param int    $uid       操作人管理员ID
     * @param string $note      修改说明/驳回原因
     */
    public static function record(int $contentId, int $type, int $uid, string $note = '')
    {
        return self::create([
            'content_id'  => $contentId,
            'type'        => $type,
            'uid'         => $uid,
            'note'        => $note,
            'create_time' => time(),
        ]);
    }

    /**
     * 某内容的全部审核日志（按时间正序），含操作人昵称
     */
    public static function logs(int $contentId)
    {
        $rows = self::where('content_id', $contentId)->order('id', 'asc')->select();
        $uids = [];
        foreach ($rows as $r) {
            if (!empty($r['uid'])) $uids[(int)$r['uid']] = 1;
        }
        $names = $uids ? Admin::whereIn('id', array_keys($uids))->column('nickname', 'id') : [];
        $out = [];
        foreach ($rows as $r) {
            // create_time 可能为 int 时间戳或 datetime 字符串（TP 自动时间戳读取时格式化）
            $ct = $r['create_time'];
            $ts = is_numeric($ct) ? (int)$ct : strtotime((string)$ct);
            $out[] = [
                'type'    => (int)$r['type'],
                'uid'     => (int)$r['uid'],
                'name'    => isset($names[(int)$r['uid']]) ? $names[(int)$r['uid']] : '系统',
                'note'    => (string)$r['note'],
                'time'    => $ts > 0 ? date('Y-m-d H:i', $ts) : '',
            ];
        }
        return $out;
    }

    /**
     * 提交次数统计（与 content.submit_count 冗余，用于校验）
     */
    public static function submitCount(int $contentId): int
    {
        return (int)self::where('content_id', $contentId)->where('type', 1)->count();
    }
}
