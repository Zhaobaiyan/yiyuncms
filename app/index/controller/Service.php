<?php
// +----------------------------------------------------------------------
// | 前台在线客服（v3.12）
// | 会员发起会话 / 发送消息 / 轮询客服回复
// | 入口：/service/chat
// | 视图：app/index/view/service/chat.html（内置视图）
// +----------------------------------------------------------------------
namespace app\index\controller;

use think\facade\Db;
use think\facade\View;

class Service extends FrontBase
{
    /** 内置视图目录 */
    protected $mv = '';

    protected function initialize()
    {
        parent::initialize();
        $this->mv = root_path() . 'app' . DIRECTORY_SEPARATOR . 'index' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'service' . DIRECTORY_SEPARATOR;
        View::assign('member', session('member_id') ? session('member') : null);
    }

    /**
     * 客服窗口（GET）：打开/复用当前会员会话
     */
    public function chat()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        if ((int)get_config('service.open', 1) !== 1) {
            abort(404, '在线客服未开启');
        }
        $uid = (int)session('member_id');
        $clientIp = request()->ip();
        // 复用未关闭会话
        $session = Db::name('service_session')->where('member_id', $uid)
            ->whereIn('status', [0, 1])->order('id', 'desc')->find();
        if (!$session) {
            $sid = Db::name('service_session')->insertGetId([
                'member_id' => $uid, 'admin_id' => 0, 'status' => 0,
                'client_ip' => $clientIp,
                'create_time' => time(), 'last_msg_time' => time(),
            ]);
            $session = Db::name('service_session')->where('id', $sid)->find();
        } elseif ($session['client_ip'] === '') {
            Db::name('service_session')->where('id', $session['id'])->update(['client_ip' => $clientIp]);
        }
        $messages = Db::name('service_msg')->where('session_id', $session['id'])->order('id', 'asc')->select();
        return $this->page('chat', [
            'title'    => '在线客服',
            'session'  => $session,
            'messages' => $messages,
        ]);
    }

    /**
     * 会员发送消息
     */
    public function send()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        if (!request()->isPost()) return $this->back('非法请求');
        $sid = (int)input('post.session_id', 0);
        $content = trim((string)input('post.content', ''));
        if ($content === '') return $this->back('消息不能为空');
        $session = Db::name('service_session')->where('id', $sid)
            ->where('member_id', (int)session('member_id'))->find();
        if (!$session) return $this->back('会话不存在');
        if ((int)$session['status'] === 2) return $this->back('会话已关闭，请重新发起咨询');
        $msgId = Db::name('service_msg')->insertGetId([
            'session_id' => $sid,
            'from_type'  => 1,
            'from_id'    => (int)session('member_id'),
            'content'    => $content,
            'client_ip'  => request()->ip(),
            'create_time'=> time(),
        ]);
        Db::name('service_session')->where('id', $sid)->update([
            'status' => 0, 'last_msg_time' => time(),
        ]);
        // 通知中心（v3.24）：通知所有开启"客服消息通知"的管理员（邮件/企业微信/短信按各自渠道）
        try {
            $member = Db::name('member')->where('id', (int)session('member_id'))->find();
            $uname = $member ? ($member['nickname'] ?: $member['username']) : ('会员#' . session('member_id'));
            \app\common\service\NotifyService::notifyAdmins(
                'service',
                '【' . (string)get_setting('site.name', '本站') . '】新客服消息',
                $uname . '：' . mb_substr($content, 0, 60),
                (string)url('admin/service/index')->domain(true)
            );
        } catch (\Throwable $e) {
        }
        return json(['code' => 1, 'msg' => 'ok', 'id' => $msgId]);
    }

    /**
     * 轮询新消息（客服回复）
     */
    public function poll()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        if (!request()->isPost()) return $this->back('非法请求');
        $sid = (int)input('post.session_id', 0);
        $lastId = (int)input('post.last_id', 0);
        $session = Db::name('service_session')->where('id', $sid)
            ->where('member_id', (int)session('member_id'))->find();
        if (!$session) return $this->back('会话不存在');
        $rows = Db::name('service_msg')->where('session_id', $sid)
            ->where('id', '>', $lastId)->order('id', 'asc')->select();
        $list = [];
        foreach ($rows as $r) {
            $list[] = [
                'id' => $r['id'], 'from_type' => (int)$r['from_type'],
                'content' => $r['content'], 'time' => date('Y-m-d H:i', (int)$r['create_time']),
            ];
        }
        return json(['code' => 1, 'list' => $list, 'status' => (int)$session['status']]);
    }

    /* ---------- 内部辅助 ---------- */

    protected function needLogin()
    {
        if (!session('member_id')) {
            if (request()->isAjax() || request()->isPost()) {
                return json(['code' => 0, 'msg' => '请先登录']);
            }
            return redirect((string)url('member/login'));
        }
        return null;
    }

    protected function back($msg): \think\response\Json
    {
        return json(['code' => 0, 'msg' => $msg]);
    }

    protected function page($tpl, $data = []): string
    {
        $data['member'] = session('member_id') ? session('member') : null;
        $data['lang']   = lang_code();
        $data['is_wap'] = $this->isWap;
        View::assign($data);
        return View::fetch($this->mv . $tpl . '.html');
    }
}
