<?php
// +----------------------------------------------------------------------
// | 后台控制台控制器
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Category;
use app\common\model\Comment;
use app\common\model\Content;
use app\common\model\Message;
use app\common\model\SearchLog;
use app\common\model\VisitorLog;

class Index extends AdminBase
{
    /**
     * 控制台首页
     */
    public function index()
    {
        $count = [
            'content'  => Content::count(),
            'category' => Category::count(),
            'message'  => Message::count(),
            'message_new' => Message::where('is_read', 0)->count(),
        ];

        // 待办事项（审核/评论）
        $todo = [];
        if ((int)get_config('content.audit', 1) === 1) {
            $auditPending = Content::where('audit_status', 1)->count();
            if ($auditPending) $todo[] = ['text' => $auditPending . ' 条内容待审核', 'url' => '/admin/content/index?status=audit1'];
        }
        try { $commentPending = Comment::pendingCount(); } catch (\Throwable $e) { $commentPending = 0; }
        if ($commentPending) $todo[] = ['text' => $commentPending . ' 条评论待审核', 'url' => '/admin/comment/index?status=0'];
        $count['comment_pending'] = $commentPending;

        // 访问统计：今日/本周/本月 PV + 近 7 日趋势（CSS 条形图数据）
        $today = strtotime(date('Y-m-d'));
        $count['today_pv'] = VisitorLog::countPv($today, $today + 86399);
        $count['week_pv']  = VisitorLog::countPv(VisitorLog::range('week')[0], time());
        $count['month_pv'] = VisitorLog::countPv(VisitorLog::range('month')[0], time());
        $trend = [];
        $maxPv = 1;
        for ($i = 6; $i >= 0; $i--) {
            $s = $today - $i * 86400;
            $pv = VisitorLog::countPv($s, $s + 86399);
            $maxPv = max($maxPv, $pv);
            $trend[] = ['day' => date('m-d', $s), 'pv' => $pv];
        }

        // 最近留言 / 热门搜索 / 最近访问
        $messages  = Message::order('id', 'desc')->limit(6)->select();
        try { $hotSearch = SearchLog::hot(6); } catch (\Throwable $e) { $hotSearch = []; }
        $visits = VisitorLog::recent(10);
        $logs = AdminLog::order('id', 'desc')->limit(8)->select();

        return view('index/index', [
            'count'    => $count,
            'todo'     => $todo,
            'trend'    => $trend,
            'maxPv'    => $maxPv,
            'messages' => $messages,
            'hotSearch'=> $hotSearch,
            'visits'   => $visits,
            'logs'     => $logs,
        ]);
    }
}
