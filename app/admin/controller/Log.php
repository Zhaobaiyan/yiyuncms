<?php
// +----------------------------------------------------------------------
// | 日志管理控制器：查看网站全部操作日志，支持一键清理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;

class Log extends AdminBase
{
    /**
     * 日志列表（分页，按时间倒序）
     */
    public function index()
    {
        $page  = max(1, (int)input('get.page', 1));
        $size  = 15;
        $total = AdminLog::count();
        $logs  = AdminLog::order('id', 'desc')->page($page, $size)->select();
        $pages = max(1, (int)ceil($total / $size));

        return view('log/index', [
            'logs'  => $logs,
            'total' => $total,
            'page'  => min($page, $pages),
            'pages' => $pages,
        ]);
    }

    /**
     * 一键清理日志
     */
    public function clear()
    {
        AdminLog::where('id', '>', 0)->delete();
        // 清空后保留一条记录，便于留痕
        AdminLog::record('log_clear', '清空全部操作日志');
        return $this->ok('日志已清空', (string)url('admin/log/index'));
    }
}
