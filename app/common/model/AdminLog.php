<?php
// +----------------------------------------------------------------------
// | 后台日志模型
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Model;

class AdminLog extends Model
{
    protected $name = 'admin_log';
    protected $autoWriteTimestamp = 'int';

    /**
     * 记录日志
     */
    public static function record($action, $remark = '')
    {
        static::create([
            'admin_id' => (int)session('admin_id'),
            'username' => (string)session('admin_name'),
            'action'   => $action,
            'remark'   => $remark,
            'ip'       => request()->ip(),
        ]);
    }
}
