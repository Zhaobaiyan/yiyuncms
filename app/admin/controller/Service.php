<?php
// +----------------------------------------------------------------------
// | 后台客服工作台（v3.12）
// | 会话列表 / 在线回复 / 轮询新消息 / 关闭会话
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use think\facade\Db;

class Service extends AdminBase
{
    /**
     * 会话列表
     */
    public function index()
    {
        $status = (int)input('status', -1);
        $q = Db::name('service_session');
        if ($status >= 0) $q->where('status', $status);
        $q->order('last_msg_time', 'desc');
        $list = $q->paginate([
            'list_rows' => 15,
            'query'     => ['status' => $status],
        ]);
        $rows = [];
        foreach ($list as $r) {
            $member = Db::name('member')->where('id', $r['member_id'])->find();
            $r['username'] = $member ? $member['username'] : '未知';
            $r['unread'] = Db::name('service_msg')->where('session_id', $r['id'])
                ->where('from_type', 1)->where('is_read', 0)->count();
            $r['ip'] = (string)($r['client_ip'] ?? '');
            $rows[] = $r;
        }
        return view('service/index', [
            'list'  => $rows,
            'pages' => $list->render(),
            'cur'   => $status,
        ]);
    }

    /**
     * 客服工作台（接待并展示消息）
     */
    public function chat()
    {
        $id = (int)input('id', 0);
        $session = Db::name('service_session')->where('id', $id)->find();
        if (!$session) return $this->fail('会话不存在');
        Db::name('service_session')->where('id', $id)->update([
            'admin_id' => (int)session('admin_id'),
            'status'   => 1,
        ]);
        // 标记会员消息已读
        Db::name('service_msg')->where('session_id', $id)->where('from_type', 1)->update(['is_read' => 1]);
        $member = Db::name('member')->where('id', $session['member_id'])->find();
        $messages = Db::name('service_msg')->where('session_id', $id)->order('id', 'asc')->select()->toArray();
        return view('service/chat', [
            'session'  => $session,
            'member'   => $member,
            'messages' => $messages,
            'client_ip' => (string)($session['client_ip'] ?? ''),
        ]);
    }

    /**
     * 发送消息
     */
    public function send()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $sessionId = (int)input('post.session_id', 0);
        $content = trim((string)input('post.content', ''));
        if ($content === '') return $this->fail('消息不能为空');
        $session = Db::name('service_session')->where('id', $sessionId)->find();
        if (!$session) return $this->fail('会话不存在');
        Db::name('service_msg')->insert([
            'session_id' => $sessionId,
            'from_type'  => 2,
            'from_id'    => (int)session('admin_id'),
            'content'    => $content,
            'create_time'=> time(),
        ]);
        Db::name('service_session')->where('id', $sessionId)->update(['last_msg_time' => time()]);
        return $this->ok('ok');
    }

    /**
     * 轮询新消息（工作台自动刷新）
     */
    public function poll()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $sessionId = (int)input('post.session_id', 0);
        $lastId = (int)input('post.last_id', 0);
        Db::name('service_msg')->where('session_id', $sessionId)
            ->where('from_type', 1)->where('id', '>', $lastId)->update(['is_read' => 1]);
        $rows = Db::name('service_msg')->where('session_id', $sessionId)
            ->where('id', '>', $lastId)->order('id', 'asc')->select();
        $list = [];
        foreach ($rows as $r) {
            $list[] = [
                'id' => $r['id'], 'from_type' => (int)$r['from_type'],
                'content' => $r['content'], 'time' => date('Y-m-d H:i', (int)$r['create_time']),
            ];
        }
        return json(['code' => 1, 'list' => $list]);
    }

    /**
     * 关闭会话
     */
    public function close()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $id = (int)input('post.id', 0);
        Db::name('service_session')->where('id', $id)->update(['status' => 2]);
        AdminLog::record('service_close', '关闭客服会话 #' . $id);
        return $this->ok('会话已关闭');
    }

    /**
     * 后台全局通知轮询（v3.24）：返回当前管理员的未读客服消息数 / 新会话数 / 未读留言数
     * 按该管理员 notify_service / notify_guestbook 独立开关计算
     */
    public function notifyCount()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $admin = Db::name('admin')->where('id', (int)session('admin_id'))->find();
        if (!$admin) return json(['code' => 0, 'msg' => '未登录']);

        $service = 0; $newSession = 0;
        if ((int)($admin['notify_service'] ?? 1) === 1) {
            // 未读会员消息
            $service = (int)Db::name('service_msg')->alias('m')
                ->join('service_session s', 'm.session_id=s.id')
                ->where('m.from_type', 1)->where('m.is_read', 0)->count();
            // 待接待新会话（无客服接手的）
            $newSession = (int)Db::name('service_session')->where('admin_id', 0)->where('status', 0)->count();
        }

        $guestbook = 0;
        if ((int)($admin['notify_guestbook'] ?? 0) === 1) {
            $tbl = 'yy_' . lang_code() . '_message';
            try {
                $guestbook = (int)Db::table($tbl)->where('is_read', 0)->count();
            } catch (\Throwable $e) {
                $guestbook = 0;
            }
        }

        return json([
            'code' => 1,
            'service'   => $service,
            'newSession'=> $newSession,
            'guestbook' => $guestbook,
            'total'     => $service + $newSession + $guestbook,
        ]);
    }

    /**
     * 通知中心已读（v3.26.1）：管理员点击铃铛/打开通知中心时调用，
     * 将当前管理员可感知的未读全部置为已读，使铃铛红点与标题闪烁立即消失：
     * 1) 全部未读会员客服消息 is_read=1
     * 2) 未接待新会话归属当前管理员（视为已查看，不自动改状态）
     * 3) 未读留言标已读（仅当该管理员开启留言通知时）
     */
    public function notifyRead()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $adminId = (int)session('admin_id');
        $admin = Db::name('admin')->where('id', $adminId)->find();
        if (!$admin) return json(['code' => 0, 'msg' => '未登录']);

        Db::name('service_msg')->where('from_type', 1)->where('is_read', 0)->update(['is_read' => 1]);
        Db::name('service_session')->where('admin_id', 0)->where('status', 0)->update(['admin_id' => $adminId]);

        if ((int)($admin['notify_guestbook'] ?? 0) === 1) {
            $tbl = 'yy_' . lang_code() . '_message';
            try {
                Db::table($tbl)->where('is_read', 0)->update(['is_read' => 1]);
            } catch (\Throwable $e) {
                // 留言表无 is_read 字段等异常时忽略
            }
        }
        return $this->ok('已全部标记已读');
    }
}
