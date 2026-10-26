<?php
// +----------------------------------------------------------------------
// | 前台会员中心控制器（v3.10 演示版）
// | 功能：注册 / 登录 / 退出 / 我的中心（资料 + 我的收藏 + 我的留言）
// | 入口：/member/register  /member/login  /member/center  /member/logout
// | 视图：app/index/view/member/（内置视图，独立样式，不依赖模板 header/footer）
// +----------------------------------------------------------------------
namespace app\index\controller;

use app\common\model\Content;
use app\common\model\Favorite;
use app\common\model\Member as MemberModel;
use app\common\model\Message;
use think\facade\Db;
use think\facade\View;

class Member extends FrontBase
{
    /** 内置视图目录（绝对路径，绕过模板 view_path） */
    protected $mv = '';

    protected function initialize()
    {
        parent::initialize();
        $this->mv = root_path() . 'app' . DIRECTORY_SEPARATOR . 'index' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'member' . DIRECTORY_SEPARATOR;
        // 登录态注入
        View::assign('member', session('member_id') ? session('member') : null);
    }

    /**
     * 注册页（GET 渲染 / POST 提交）
     * v3.12：支持注册开关 / 注册验证码 / 注册审核
     */
    public function register()
    {
        if (session('member_id')) {
            return redirect((string)url('member/center'));
        }
        // 注册开关
        $regOpen = (int)get_config('member.reg_open', 1);
        if (request()->isPost()) {
            if ($regOpen !== 1) {
                return $this->back('网站暂未开放会员注册');
            }
            // 注册验证码（防恶意注册）
            $regCaptcha = (int)get_config('member.reg_captcha', 1);
            if ($regCaptcha === 1 && !captcha_check((string)input('post.captcha', ''))) {
                return $this->back('验证码错误');
            }
            $username = trim((string)input('post.username', ''));
            $password = (string)input('post.password', '');
            $password2 = (string)input('post.password2', '');
            $nickname = trim((string)input('post.nickname', ''));
            $email    = trim((string)input('post.email', ''));

            if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
                return $this->back('用户名需 3-30 位字母、数字或下划线');
            }
            if (strlen($password) < 6) {
                return $this->back('密码至少 6 位');
            }
            if ($password !== $password2) {
                return $this->back('两次密码不一致');
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->back('邮箱格式不正确');
            }
            if (MemberModel::where('username', $username)->find()) {
                return $this->back('该用户名已被注册');
            }
            // 注册审核开关：开启则待审核，关闭则直接生效
            $needAudit = (int)get_config('member.audit', 0) === 1;
            $row = MemberModel::create([
                'username'    => $username,
                'password'    => $password,
                'nickname'    => $nickname ?: $username,
                'email'       => $email,
                'status'      => $needAudit ? 0 : 1,
                'reg_ip'      => request()->ip(),
                'create_time' => time(),
                'update_time' => time(),
            ]);
            if ($needAudit) {
                return json(['code' => 1, 'msg' => '注册成功，账号待管理员审核通过后方可登录', 'url' => url('member/login')->build()]);
            }
            $this->doLogin($row, false);
            return json(['code' => 1, 'msg' => '注册成功，已自动登录', 'url' => url('member/center')->build()]);
        }
        return $this->page('register', [
            'title'     => '会员注册',
            'reg_open'  => $regOpen,
            'reg_captcha' => (int)get_config('member.reg_captcha', 1),
            'need_audit'  => (int)get_config('member.audit', 0),
        ]);
    }

    /**
     * 登录页（GET 渲染 / POST 提交）
     */
    public function login()
    {
        if (session('member_id')) {
            return redirect((string)url('member/center'));
        }
        if (request()->isPost()) {
            $username = trim((string)input('post.username', ''));
            $password = (string)input('post.password', '');
            // 登录验证码开关（v3.15，默认开启；测试可临时关闭）
            if (get_config('member.login_captcha', 1) == 1 && !captcha_check((string)input('post.captcha', ''))) {
                return $this->back('验证码错误');
            }
            $row = MemberModel::where('username', $username)->find();
            if (!$row || !password_verify($password, (string)$row['password'])) {
                return $this->back('用户名或密码错误');
            }
            if ((int)$row['status'] === 0) {
                return $this->back('账号待审核，请等待管理员审核通过');
            }
            if ((int)$row['status'] !== 1) {
                return $this->back('账号已被禁用');
            }
            $this->doLogin($row);
            return json(['code' => 1, 'msg' => '登录成功', 'url' => url('member/center')->build()]);
        }
        return $this->page('login', ['title' => '会员登录']);
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        session('member_id', null);
        session('member', null);
        return redirect('/');
    }

    /**
     * 我的中心：资料 + 我的收藏 + 我的留言
     */
    public function center()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        $uid  = (int)session('member_id');
        $lang = lang_code();

        // 我的收藏：favorite -> content
        $favs = [];
        $favRows = Favorite::listByMember($uid, 200)->toArray();
        if ($favRows) {
            $ids = array_column($favRows, 'content_id');
            $contents = Content::whereIn('id', $ids)->select()->toArray();
            $map = [];
            foreach ($contents as $c) $map[$c['id']] = $c;
            foreach ($favRows as $f) {
                if (isset($map[$f['content_id']])) {
                    $c = $map[$f['content_id']];
                    $favs[] = [
                        'id'      => $c['id'],
                        'title'   => $c['title'],
                        'thumb'   => $c['thumb'],
                        'views'   => $c['views'],
                        'time'    => $c['create_time'] ? date('Y-m-d', (int)$c['create_time']) : '',
                        'url'     => url('show/index', ['id' => $c['id']])->build(),
                    ];
                }
            }
        }

        // 我的留言：按邮箱匹配（留言提交时填的 email）
        $mails = [];
        if (session('member.email') !== '') $mails[] = session('member.email');
        $msgRows = [];
        if ($mails) {
            $msgRows = Message::whereIn('email', $mails)->order('id', 'desc')->limit(50)->select()->toArray();
        }
        foreach ($msgRows as &$m) {
            $m['_ct'] = $m['create_time'] ? date('Y-m-d H:i', (int)$m['create_time']) : '';
            $m['_rt'] = $m['reply_time'] ? date('Y-m-d H:i', (int)$m['reply_time']) : '';
            $m['_status_text'] = ['待审核', '已通过', '未通过'][(int)$m['status']] ?? '未知';
        }
        unset($m);

        return $this->page('center', [
            'title'  => '我的中心',
            'favs'   => $favs,
            'msgs'   => $msgRows,
            'lang'   => $lang,
        ]);
    }

    /**
     * 保存资料（POST）
     */
    public function save()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        if (!request()->isPost()) {
            return $this->back('非法请求');
        }
        $id = (int)session('member_id');
        $row = MemberModel::find($id);
        if (!$row) return $this->back('会员不存在');

        $data = [];
        $nickname = trim((string)input('post.nickname', ''));
        $email    = trim((string)input('post.email', ''));
        $phone    = trim((string)input('post.phone', ''));
        if ($nickname !== '') $data['nickname'] = $nickname;
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->back('邮箱格式不正确');
        }
        $data['email'] = $email;
        $data['phone'] = $phone;

        $old = (string)input('post.old_password', '');
        $new = (string)input('post.new_password', '');
        if ($new !== '') {
            if (!password_verify($old, (string)$row['password'])) {
                return $this->back('原密码不正确');
            }
            if (strlen($new) < 6) {
                return $this->back('新密码至少 6 位');
            }
            $data['password'] = $new;
        }

        $row->save($data);
        // 刷新 session
        $fresh = MemberModel::find($id);
        session('member', $fresh->toArray());
        return json(['code' => 1, 'msg' => '保存成功']);
    }

    /**
     * 验证码图片（登录页调用）
     */
    public function captcha()
    {
        return captcha();
    }

    /**
     * 我的钱包（v3.15）：余额 + 流水 + 提现申请
     */
    public function wallet()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        $uid = (int)session('member_id');
        $wallet = Db::name('wallet')->where('member_id', $uid)->find();
        if (!$wallet) {
            Db::name('wallet')->insert([
                'member_id' => $uid, 'balance' => 0, 'frozen' => 0,
                'total_income' => 0, 'total_withdraw' => 0, 'update_time' => time(),
            ]);
            $wallet = Db::name('wallet')->where('member_id', $uid)->find();
        }
        $logs = Db::name('wallet_log')->where('member_id', $uid)->order('id', 'desc')->limit(100)->select()->toArray();
        $withdraws = Db::name('withdraw')->where('member_id', $uid)->order('id', 'desc')->limit(50)->select()->toArray();
        $typeText = ['refund' => '退款入账', 'withdraw' => '提现', 'adjust' => '后台调整', 'income' => '收入'];
        foreach ($logs as &$l) $l['_type'] = $typeText[$l['type']] ?? $l['type'];
        unset($l);
        return $this->page('wallet', [
            'title'    => '我的钱包',
            'wallet'   => $wallet,
            'logs'     => $logs,
            'withdraws'=> $withdraws,
        ]);
    }

    /**
     * 申请提现（POST）：微信/支付宝 + 收款账号 + 姓名
     */
    public function withdraw_add()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        if (!request()->isPost()) return $this->back('非法请求');
        $uid = (int)session('member_id');
        $amount = round((float)input('post.amount', 0), 2);
        $channel = (string)input('post.channel', 'wechat');
        $account = trim((string)input('post.account', ''));
        $realName = trim((string)input('post.real_name', ''));
        if (!in_array($channel, ['wechat', 'alipay'])) return $this->back('提现渠道错误');
        if ($amount < 1) return $this->back('提现金额最低 1 元');
        if ($account === '' || $realName === '') return $this->back('请填写收款账号与姓名');
        $wallet = Db::name('wallet')->where('member_id', $uid)->find();
        if (!$wallet) return $this->back('钱包不存在');
        if ((float)$wallet['balance'] < $amount) return $this->back('余额不足');
        // 余额 -> 冻结
        Db::name('wallet')->where('member_id', $uid)->update([
            'balance' => Db::raw('balance-' . $amount),
            'frozen'  => Db::raw('frozen+' . $amount),
            'update_time' => time(),
        ]);
        Db::name('withdraw')->insert([
            'member_id' => $uid, 'amount' => $amount, 'channel' => $channel,
            'account' => $account, 'real_name' => $realName,
            'status' => 0, 'create_time' => time(),
        ]);
        $after = (float)Db::name('wallet')->where('member_id', $uid)->value('balance');
        Db::name('wallet_log')->insert([
            'member_id' => $uid, 'type' => 'withdraw', 'amount' => -$amount,
            'balance_after' => $after, 'note' => '申请提现至' . ($channel == 'wechat' ? '微信' : '支付宝'),
            'create_time' => time(),
        ]);
        return json(['code' => 1, 'msg' => '提现申请已提交，等待后台打款', 'url' => url('member/wallet')->build()]);
    }

    /* ---------- 内部辅助 ---------- */

    protected function needLogin()
    {
        if (!session('member_id')) {
            if (request()->isAjax() || request()->isPost()) {
                abort(403, '请先登录');
            }
            return redirect((string)url('member/login'));
        }
        return null;
    }

    protected function doLogin($row, $setLoginTime = true)
    {
        session('member_id', (int)$row['id']);
        session('member', $row->toArray());
        if ($setLoginTime) {
            $row->save([
                'last_login_time' => time(),
                'last_login_ip'   => request()->ip(),
                'login_count'     => (int)$row['login_count'] + 1,
            ]);
        }
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
