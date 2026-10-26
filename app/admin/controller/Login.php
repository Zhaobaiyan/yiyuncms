<?php
// +----------------------------------------------------------------------
// | 后台登录控制器
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\Admin;
use app\common\model\AdminLog;
use think\facade\Session;
use think\facade\Validate;

class Login extends AdminBase
{
    public function initialize()
    {
        // 登录页独立布局，不注入后台侧边栏数据
    }

    /**
     * 登录页
     */
    public function index()
    {
        if (Session::has('admin_id')) {
            return redirect((string)url('admin/index/index'));
        }
        $timeoutTip = Session::pull('login_timeout_tip');
        return view('login/login', ['timeout_tip' => $timeoutTip]);
    }

    /**
     * 登录处理
     */
    public function login()
    {
        if (!request()->isPost()) {
            return $this->fail('非法请求');
        }
        $username = trim(input('post.username', ''));
        $password = input('post.password', '');
        $code     = input('post.code', '');

        // 验证码校验（网站设置「后台登录验证码」开关，默认开启）
        if (get_config('admin.captcha', 1) == 1 && !captcha_check($code)) {
            return $this->fail('验证码错误');
        }

        if ($username === '' || $password === '') {
            return $this->fail('请输入账号和密码');
        }

        $admin = Admin::where('username', $username)->find();
        // 登录失败锁定：连续失败 5 次锁定 15 分钟（v3.10）
        if ($admin && (int)$admin['lock_time'] > 0 && (int)$admin['lock_time'] + 900 > time()) {
            $wait = ceil(((int)$admin['lock_time'] + 900 - time()) / 60);
            return $this->fail('登录失败次数过多，账号已临时锁定，请 ' . $wait . ' 分钟后再试');
        }
        $fail = 0;
        if (!$admin || !$admin->verifyPassword($password)) {
            if ($admin) {
                $fail = (int)$admin['login_fail'] + 1;
                $admin->save(['login_fail' => $fail, 'lock_time' => $fail >= 5 ? time() : 0]);
            }
            AdminLog::record('login_fail', '账号或密码错误：' . $username);
            return $this->fail($fail >= 5 ? '账号或密码错误（连续失败 5 次将锁定 15 分钟）' : '账号或密码错误');
        }
        if ($admin['status'] != 1) {
            return $this->fail('该账号已被禁用');
        }
        // 登录成功：重置失败计数
        $admin->save(['login_fail' => 0, 'lock_time' => 0]);

        // 两步验证：已设置二次验证码（PIN）的管理员先进入二次验证页
        if (!empty($admin['twofa_secret'])) {
            Session::set('twofa_admin_id', $admin['id']);
            return $this->ok('请输入二次验证码', (string)url('admin/login/twofa'));
        }

        // 登录成功
        Session::set('admin_id', $admin['id']);
        Session::set('admin_name', $admin['username']);
        Session::set('admin_nick', $admin['nickname'] ?: $admin['username']);
        Session::set('admin_role', $admin['role']);

        $admin->save([
            'last_login_time' => time(),
            'last_login_ip'   => request()->ip(),
            'login_count'     => $admin['login_count'] + 1,
        ]);
        AdminLog::record('login', '登录后台成功');

        return $this->ok('登录成功', (string)url('admin/index/index'));
    }

    /**
     * 两步验证（v3.10）：PIN 输入页 / 验证
     * 管理员在「管理员管理」中设置二次验证码后，登录时除账号/密码/验证码外需再输入 PIN
     */
    public function twofa()
    {
        $uid = (int)Session::get('twofa_admin_id');
        if (!$uid) {
            return redirect((string)url('admin/login/index'));
        }
        if (request()->isPost()) {
            $pin   = trim(input('post.pin', ''));
            $admin = Admin::find($uid);
            if (!$admin || !password_verify($pin, (string)$admin['twofa_secret'])) {
                AdminLog::record('twofa_fail', '二次验证失败：' . ($admin ? $admin['username'] : $uid));
                return $this->fail('二次验证码错误');
            }
            Session::set('admin_id', $admin['id']);
            Session::set('admin_name', $admin['username']);
            Session::set('admin_nick', $admin['nickname'] ?: $admin['username']);
            Session::set('admin_role', $admin['role']);
            Session::delete('twofa_admin_id');
            $admin->save([
                'last_login_time' => time(),
                'last_login_ip'   => request()->ip(),
                'login_count'     => $admin['login_count'] + 1,
            ]);
            AdminLog::record('login', '登录后台成功（两步验证）');
            return $this->ok('登录成功', (string)url('admin/index/index'));
        }
        return view('login/twofa');
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        AdminLog::record('logout', '退出后台');
        Session::delete('admin_id');
        Session::delete('admin_name');
        Session::delete('admin_nick');
        Session::delete('admin_role');
        Session::delete('twofa_admin_id');
        if (input('get.timeout')) {
            Session::set('login_timeout_tip', 1);
        }
        return redirect((string)url('admin/login/index'));
    }

    /**
     * 验证码（供登录页调用）
     */
    public function captcha()
    {
        return captcha();
    }
}
