<?php
// +----------------------------------------------------------------------
// | 后台会员管理（v3.12）
// | 会员列表 / 注册审核 / 会员设置（注册开关、验证码开关、审核开关）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\Member as MemberModel;
use think\facade\Db;

class MemberAdmin extends AdminBase
{
    /**
     * 会员列表
     */
    public function index()
    {
        $status  = (int)input('status', -2);   // -2 全部  -1 禁用  0 待审核  1 正常
        $keyword = trim((string)input('keyword', ''));
        $q = MemberModel::order('id', 'desc');
        if ($status >= -1) $q->where('status', $status);
        if ($keyword !== '') {
            $q->where('username|nickname|email|phone', 'like', '%' . $keyword . '%');
        }
        $list = $q->paginate([
            'list_rows' => 20,
            'query'     => ['status' => $status, 'keyword' => $keyword],
        ]);
        return view('member_admin/index', [
            'list'    => $list,
            'pages'   => $list->render(),
            'cur'     => $status,
            'keyword' => $keyword,
        ]);
    }

    /**
     * 添加 / 编辑会员（后台管理员自行新增会员或修改会员信息，v3.21）
     */
    public function edit()
    {
        $id  = (int)input('id', 0);
        if (request()->isPost()) {
            $username = trim((string)input('post.username', ''));
            $nickname = trim((string)input('post.nickname', ''));
            $email    = trim((string)input('post.email', ''));
            $phone    = trim((string)input('post.phone', ''));
            $avatar   = trim((string)input('post.avatar', ''));
            $status   = (int)input('post.status', 1);
            $password = (string)input('post.password', '');
            if ($username === '') return $this->fail('请填写会员账号');
            $dup = MemberModel::where('username', $username);
            if ($id) $dup = $dup->where('id', '<>', $id);
            if ($dup->find()) return $this->fail('该会员账号已存在');

            if ($id) {
                $member = MemberModel::find($id);
                if (!$member) return $this->fail('会员不存在');
                $data = ['username' => $username, 'nickname' => $nickname, 'email' => $email, 'phone' => $phone, 'avatar' => $avatar, 'status' => $status];
                if ($password !== '') $data['password'] = password_hash($password, PASSWORD_DEFAULT);
                $member->save($data);
                AdminLog::record('member_edit', '修改会员：' . $username . '（#' . $id . '）');
                return $this->ok('会员信息已保存', (string)url('admin/member_admin/index'));
            }
            if ($password === '') return $this->fail('请填写会员密码');
            MemberModel::create([
                'username' => $username, 'password' => password_hash($password, PASSWORD_DEFAULT),
                'nickname' => $nickname, 'email' => $email, 'phone' => $phone, 'avatar' => $avatar,
                'status' => $status, 'create_time' => time(), 'update_time' => time(),
            ]);
            AdminLog::record('member_add', '后台新增会员：' . $username);
            return $this->ok('会员添加成功', (string)url('admin/member_admin/index'));
        }

        $row = $id ? (MemberModel::find($id) ?: ['id' => 0, 'username' => '', 'nickname' => '', 'email' => '', 'phone' => '', 'avatar' => '', 'status' => 1]) : ['id' => 0, 'username' => '', 'nickname' => '', 'email' => '', 'phone' => '', 'avatar' => '', 'status' => 1];
        return view('member_admin/edit', ['row' => $row]);
    }

    /**
     * 审核（通过 / 拒绝）
     */
    public function audit()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $id     = (int)input('post.id', 0);
        $status = (int)input('post.status', 0);
        $remark = trim((string)input('post.remark', ''));
        if (!in_array($status, [1, -1])) return $this->fail('参数错误');
        $member = MemberModel::find($id);
        if (!$member) return $this->fail('会员不存在');
        $member->save([
            'status'       => $status,
            'audit_remark' => $remark,
        ]);
        AdminLog::record('member_audit', '会员审核：' . $member['username'] . ' -> ' . ($status == 1 ? '通过' : '拒绝') . ($remark ? '（' . $remark . '）' : ''));
        return $this->ok('操作成功', (string)url('admin/member_admin/index'));
    }

    /**
     * 会员设置（POST 保存 / GET 渲染）
     */
    public function setting()
    {
        if (request()->isPost()) {
            set_config('member.reg_open', (int)input('post.reg_open', 1));
            set_config('member.reg_captcha', (int)input('post.reg_captcha', 1));
            set_config('member.audit', (int)input('post.audit', 0));
            AdminLog::record('member_setting', '修改会员设置');
            return $this->ok('设置已保存', (string)url('admin/member_admin/setting'));
        }
        return view('member_admin/setting', [
            'reg_open'    => (int)get_config('member.reg_open', 1),
            'reg_captcha' => (int)get_config('member.reg_captcha', 1),
            'audit'       => (int)get_config('member.audit', 0),
        ]);
    }

    /**
     * 删除会员
     */
    public function delete($id = 0)
    {
        $member = MemberModel::find($id);
        if ($member) {
            Db::name('cart')->where('member_id', $id)->delete();
            $member->delete();
            AdminLog::record('member_delete', '删除会员：' . $member['username']);
        }
        return $this->ok('删除成功', (string)url('admin/member_admin/index'));
    }
}
