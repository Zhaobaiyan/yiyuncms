<?php
// +----------------------------------------------------------------------
// | 前台留言控制器
// | 支持多留言板：按留言板字段动态校验（必填即时提醒），动态字段存 extend
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\common\model\Message as MessageModel;
use app\common\model\MessageBoard;

class Message extends FrontBase
{
    /**
     * 留言提交（AJAX）
     */
    public function submit()
    {
        if (!request()->isPost()) {
            return json(['code' => 0, 'msg' => '非法请求']);
        }

        $boardId = (int)input('post.board_id', 0);
        $board   = null;
        if ($boardId > 0) {
            $board = MessageBoard::find($boardId);
            if (!$board || $board['status'] != 1) {
                return json(['code' => 0, 'msg' => '留言板不存在或已停用']);
            }
        }

        // 验证码校验（该留言板开启验证码时）
        if ($board && (int)$board['captcha'] === 1) {
            if (!captcha_check(input('post.captcha_code', ''))) {
                return json(['code' => 0, 'msg' => '验证码错误']);
            }
        }

        $post  = input('post.');
        // 标准字段
        $data  = [
            'name'    => trim(input('post.name', '')),
            'phone'   => trim(input('post.phone', '')),
            'email'   => trim(input('post.email', '')),
            'title'   => trim(input('post.title', '')),
            'content' => trim(input('post.content', '')),
            'ip'      => request()->ip(),
        ];
        $data['board_id'] = $boardId;

        // 动态字段收集 + 必填校验
        $extend = [];
        $fields = $board ? $board['fields'] : $this->defaultFields();
        foreach ($fields as $f) {
            $fname = $f['name'];
            // 标准字段已单独处理，跳过
            if (in_array($fname, ['name', 'phone', 'email', 'title', 'content'])) continue;
            $val = isset($post[$fname]) ? $post[$fname] : '';
            if (is_array($val)) $val = implode(',', $val);
            $val = trim((string)$val);
            if ((int)$f['required'] === 1 && $val === '') {
                return json(['code' => 0, 'msg' => '请填写' . $f['label']]);
            }
            if ($val !== '') {
                $extend[$fname] = $val;
            }
        }
        // 标准字段必填校验（默认留言板保留原规则）
        if ($data['name'] === '' || $data['content'] === '') {
            return json(['code' => 0, 'msg' => '请填写称呼和留言内容']);
        }
        if (mb_strlen($data['content']) > 2000) {
            return json(['code' => 0, 'msg' => '留言内容过长']);
        }
        $data['extend'] = $extend ? json_encode($extend, JSON_UNESCAPED_UNICODE) : '';

        // 是否公开：留言板开启"前台选择"时按提交值；未开启按留言板默认公开
        $postedPublic = (int)input('post.is_public', -1);
        if ($board && (int)$board['show_public'] === 1) {
            $data['is_public'] = $postedPublic === 1 ? 1 : 0;
        } elseif ($board) {
            $data['is_public'] = (int)$board['default_public'] === 1 ? 1 : 0;
        } else {
            $data['is_public'] = $postedPublic === 1 ? 1 : 0;
        }

        $audit = get_setting('message.audit', 1);
        $data['status'] = $audit == 1 ? 0 : 1;
        // 查询码：用于前台查询留言回复情况（公开/不公开均可查询）
        $data['query_code'] = $this->genQueryCode();

        $info = MessageModel::create($data);

        // 邮件通知：后台「网站设置→内容与留言→新留言邮件通知」开启时发送（失败不影响留言提交结果）
        try {
            if ((int)get_setting('message.notify', 0) === 1) {
                \app\common\service\MailService::sendMessageNotify($info, $board);
            }
        } catch (\Throwable $e) {
        }
        // 通知中心（v3.24）：通知所有开启"留言通知"的管理员（邮件/企业微信/短信按各自渠道，管理员独立开关）
        try {
            \app\common\service\NotifyService::notifyAdmins(
                'guestbook',
                '【' . (string)get_setting('site.name', '本站') . '】新留言',
                '留言板：' . ($board ? $board['name'] : '默认留言板') . '，' . $data['name'] . '（' . $data['phone'] . '）：' . mb_substr($data['content'], 0, 60) . '（IP：' . $data['ip'] . '）',
                (string)url('admin/message/index')->domain(true)
            );
        } catch (\Throwable $e) {
        }

        return json([
            'code' => 1,
            'msg'  => ($audit == 1 ? '提交成功，等待审核。' : '提交成功。') . '请牢记查询码：' . $data['query_code'] . '（凭此码可在留言页面查询回复情况）',
            'data' => ['query_code' => $data['query_code']],
        ]);
    }

    /**
     * 生成唯一查询码（8 位大写字母数字）
     */
    protected function genQueryCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max   = strlen($chars) - 1;
        for ($i = 0; $i < 10; $i++) {
            $code = '';
            for ($j = 0; $j < 8; $j++) {
                $code .= $chars[random_int(0, $max)];
            }
            if (!MessageModel::where('query_code', $code)->find()) {
                return $code;
            }
        }
        return $code . (string)time();
    }

    /**
     * 凭查询码查询留言回复情况（AJAX POST）
     */
    public function query()
    {
        $code = trim((string)input('post.query_code', ''));
        if ($code === '') {
            return json(['code' => 0, 'msg' => '请输入查询码']);
        }
        $msg = MessageModel::where('query_code', $code)->find();
        if (!$msg) {
            return json(['code' => 0, 'msg' => '查询码不存在，请核对后重试']);
        }
        $statusText = ['0' => '待审核', '1' => '已通过', '2' => '未通过'];
        $ct = $msg->getData('create_time') ?: 0;
        $rt = $msg->getData('reply_time') ?: 0;
        return json([
            'code' => 1,
            'data' => [
                'name'       => $msg['name'],
                'title'      => $msg['title'],
                'content'    => $msg['content'],
                'create_time'=> $ct ? date('Y-m-d H:i', (int)$ct) : '',
                'status'     => $msg['status'],
                'status_text'=> $statusText[$msg['status']] ?? '未知',
                'reply'      => (string)$msg['reply'],
                'reply_time' => $rt ? date('Y-m-d H:i', (int)$rt) : '',
                'is_public'  => (int)$msg['is_public'],
            ],
        ]);
    }

    /**
     * 兼容格式化时间：int 时间戳或已格式化的 datetime 字符串均可
     */
    protected function fmtTs($v): string
    {
        if (!$v) return '';
        if (is_numeric($v)) {
            $ts = (int)$v;
        } else {
            $ts = strtotime((string)$v);
        }
        return $ts ? date('Y-m-d H:i', $ts) : '';
    }

    /**
     * 公开留言列表（status=1 且 is_public=1），供前台调用展示
     */
    public function list()
    {
        $boardId = (int)input('get.board_id', 0);
        $limit   = (int)input('get.limit', 20);
        $limit   = $limit > 0 && $limit <= 100 ? $limit : 20;
        $query   = MessageModel::where('status', 1)->where('is_public', 1)->order('id', 'desc');
        if ($boardId > 0) {
            $query->where('board_id', $boardId);
        }
        $rows = $query->limit($limit)->select()->toArray();
        foreach ($rows as &$r) {
            $r['create_time'] = $this->fmtTs($r['create_time'] ?? 0);
            $r['reply_time']  = $this->fmtTs($r['reply_time'] ?? 0);
        }
        unset($r);
        return json(['code' => 1, 'data' => $rows]);
    }

    /**
     * 验证码图片（留言板开启验证码时前台调用）
     */
    public function captcha()
    {
        return captcha();
    }

    /**
     * 获取指定留言板（供前台表单渲染）
     */
    public function board($id = 0)
    {
        $id = (int)$id;
        $board = $id > 0 ? MessageBoard::find($id) : MessageBoard::order('sort', 'asc')->order('id', 'asc')->where('status', 1)->find();
        if (!$board || $board['status'] != 1) {
            return json(['code' => 0, 'msg' => '留言板不存在']);
        }
        return json(['code' => 1, 'board' => $board->toArray()]);
    }

    /**
     * 默认字段（无留言板时兜底）
     */
    protected function defaultFields(): array
    {
        return [
            ['name' => 'name', 'label' => '称呼', 'required' => 1],
            ['name' => 'content', 'label' => '留言内容', 'required' => 1],
        ];
    }
}
