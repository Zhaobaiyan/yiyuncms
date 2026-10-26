<?php
// +----------------------------------------------------------------------
// | 管理员管理控制器
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\Admin as AdminModel;
use app\common\model\AdminRole as RoleModel;
use app\common\model\AdminLog;

class Admin extends AdminBase
{
    public function index()
    {
        $list  = AdminModel::order('id', 'asc')->select();
        $roles = RoleModel::where('status', 1)->order('sort', 'asc')->order('id', 'asc')->select();
        $roleMap = [];
        foreach ($roles as $r) { $roleMap[$r['code']] = $r['name']; }
        foreach ($list as &$a) { $a['role_name'] = $roleMap[$a['role']] ?? ($a['role'] === 'super' ? '超级管理员' : $a['role']); }
        unset($a);
        return view('admin/index', ['list' => $list, 'roles' => $roles]);
    }

    public function edit($id = 0)
    {
        if (request()->isPost()) {
            $username = trim(input('post.username', ''));
            $password = input('post.password', '');
            $nickname = input('post.nickname', '');
            $role     = input('post.role', 'admin');
            $status   = intval(input('post.status', 1));
            // 两步验证（v3.10）：输入 6 位 PIN 则启用；勾选清除则关闭
            $twofaPin = trim((string)input('post.twofa_pin', ''));
            $clearTwofa = (int)input('post.clear_twofa', 0) === 1;

            if ($id) {
                $admin = AdminModel::find($id);
                if (!$admin) return $this->fail('管理员不存在');
                // 不能把自己从超级管理员降级（防止锁死）
                $curId = session('admin_id');
                if ($curId == $id && $admin['role'] === 'super' && $role !== 'super') {
                    return $this->fail('不能将当前登录的超级管理员降级');
                }
                if ($password !== '') {
                    $admin->save(['password' => $password]);
                }
                $save2 = [
                    'username' => $username,
                    'nickname' => $nickname,
                    'role'     => $role,
                    'status'   => $status,
                    // 通知中心（v3.24）：每个管理员独立开关与渠道
                    'notify_service'   => intval(input('post.notify_service', 0)) === 1 ? 1 : 0,
                    'notify_guestbook' => intval(input('post.notify_guestbook', 0)) === 1 ? 1 : 0,
                    'notify_email'     => trim((string)input('post.notify_email', '')),
                    'notify_phone'     => trim((string)input('post.notify_phone', '')),
                    'wxwork_webhook'   => trim((string)input('post.wxwork_webhook', '')),
                ];
                // 站点/语言范围（v3.20）：JSON 数组，空=全部
                $scope = input('post.site_scope', '');
                $scopeArr = is_array($scope) ? array_values(array_filter(array_map('trim', $scope))) : [];
                $save2['site_scope'] = $scopeArr ? json_encode($scopeArr, JSON_UNESCAPED_UNICODE) : '';
                if ($twofaPin !== '') {
                    if (!preg_match('/^\d{6}$/', $twofaPin)) return $this->fail('两步验证码需为 6 位数字');
                    $save2['twofa_secret'] = password_hash($twofaPin, PASSWORD_DEFAULT);
                } elseif ($clearTwofa && !empty($admin['twofa_secret'])) {
                    $save2['twofa_secret'] = '';
                }
                $admin->save($save2);
                AdminLog::record('admin_edit', '编辑管理员：' . $username . ($twofaPin !== '' ? '（更新两步验证）' : ''));
            } else {
                if (AdminModel::where('username', $username)->find()) {
                    return $this->fail('账号已存在');
                }
                if ($password === '') return $this->fail('请设置密码');
                $create = [
                    'username' => $username,
                    'password' => $password,
                    'nickname' => $nickname,
                    'role'     => $role,
                    'status'   => $status,
                    // 通知中心（v3.24）
                    'notify_service'   => intval(input('post.notify_service', 0)) === 1 ? 1 : 0,
                    'notify_guestbook' => intval(input('post.notify_guestbook', 0)) === 1 ? 1 : 0,
                    'notify_email'     => trim((string)input('post.notify_email', '')),
                    'notify_phone'     => trim((string)input('post.notify_phone', '')),
                    'wxwork_webhook'   => trim((string)input('post.wxwork_webhook', '')),
                ];
                $scope = input('post.site_scope', '');
                $scopeArr = is_array($scope) ? array_values(array_filter(array_map('trim', $scope))) : [];
                $create['site_scope'] = $scopeArr ? json_encode($scopeArr, JSON_UNESCAPED_UNICODE) : '';
                if ($twofaPin !== '') {
                    if (!preg_match('/^\d{6}$/', $twofaPin)) return $this->fail('两步验证码需为 6 位数字');
                    $create['twofa_secret'] = password_hash($twofaPin, PASSWORD_DEFAULT);
                }
                AdminModel::create($create);
                AdminLog::record('admin_add', '新增管理员：' . $username);
            }
            return $this->ok('保存成功', (string)url('admin/admin/index'));
        }

        $info  = $id ? AdminModel::find($id) : null;
        $roles = RoleModel::where('status', 1)->order('sort', 'asc')->order('id', 'asc')->select();
        // 站点/语言范围选项（v3.20）：主站 + 启用路径站点 + 启用语言版本
        $siteOptions = [];
        try {
            $default = default_lang();
            $siteOptions[] = ['key' => 'main', 'name' => '主站（' . $default . '）'];
            if ((int)get_config('site.multi', 0) === 1) {
                foreach (\think\facade\Db::name('site')->where('status', 1)->order('sort', 'asc')->select() as $st) {
                    $siteOptions[] = ['key' => (string)$st['alias'], 'name' => $st['name'] . '（' . $st['alias'] . '）'];
                }
            }
            foreach (\app\common\model\Lang::where('is_open', 1)->order('sort', 'asc')->column('code') as $code) {
                $hit = false;
                foreach ($siteOptions as $s) { if ($s['key'] === $code) { $hit = true; break; } }
                if (!$hit) $siteOptions[] = ['key' => $code, 'name' => '语言站 ' . $code];
            }
        } catch (\Throwable $e) {}
        $curScope = [];
        if ($info && trim((string)$info['site_scope']) !== '') {
            $curScope = json_decode((string)$info['site_scope'], true) ?: [];
        }
        return view('admin/edit', ['info' => $info, 'roles' => $roles, 'site_options' => $siteOptions, 'cur_scope' => $curScope]);
    }

    public function delete($id = 0)
    {
        if ($id == 1) return $this->fail('超级管理员不可删除');
        if ($id == session('admin_id')) return $this->fail('不能删除当前登录的账号');
        $admin = AdminModel::find($id);
        if ($admin) {
            $admin->delete();
            AdminLog::record('admin_delete', '删除管理员：' . $admin['username']);
        }
        return $this->ok('删除成功', (string)url('admin/admin/index'));
    }
}
