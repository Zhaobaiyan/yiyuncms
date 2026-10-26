<?php
// +----------------------------------------------------------------------
// | 后台钱包管理（v3.15）
// | 会员钱包列表 / 钱包明细 / 提现审核（打款、拒绝） / 余额调整
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use think\facade\Db;
use think\facade\View;

class Wallet extends AdminBase
{
    /**
     * 钱包列表（会员 + 余额 + 累计）
     */
    public function index()
    {
        $keyword = trim((string)input('keyword', ''));
        $q = Db::name('wallet')->alias('w')->field('w.*,m.username,m.nickname')
            ->leftJoin('member m', 'm.id=w.member_id');
        if ($keyword !== '') $q->whereLike('m.username|m.nickname', '%' . $keyword . '%');
        $q->order('w.id', 'desc');
        $list = $q->paginate([
            'list_rows' => 15,
            'query'     => ['keyword' => $keyword],
        ]);
        return view('wallet/index', [
            'list'   => $list,
            'pages'  => $list->render(),
            'keyword'=> $keyword,
        ]);
    }

    /**
     * 钱包明细（流水 + 提现记录 + 余额调整）
     */
    public function detail()
    {
        $memberId = (int)input('member_id', 0);
        if ($memberId <= 0) return $this->fail('参数错误');
        $wallet = Db::name('wallet')->where('member_id', $memberId)->find();
        $member = Db::name('member')->where('id', $memberId)->find();
        if (!$member) return $this->fail('会员不存在');
        if (!$wallet) {
            Db::name('wallet')->insert([
                'member_id' => $memberId, 'balance' => 0, 'frozen' => 0,
                'total_income' => 0, 'total_withdraw' => 0, 'update_time' => time(),
            ]);
            $wallet = Db::name('wallet')->where('member_id', $memberId)->find();
        }
        $logs = Db::name('wallet_log')->where('member_id', $memberId)->order('id', 'desc')->limit(200)->select();
        $withdraws = Db::name('withdraw')->where('member_id', $memberId)->order('id', 'desc')->limit(100)->select();
        $typeText = ['refund' => '退款入账', 'withdraw' => '提现', 'adjust' => '后台调整', 'income' => '收入'];
        // Collection 需先 toArray 才能按引用写 _type（ArrayIterator foreach & 不生效）
        $logsArr = $logs->toArray();
        foreach ($logsArr as &$l) $l['_type'] = $typeText[$l['type']] ?? $l['type'];
        unset($l);
        return view('wallet/detail', [
            'wallet'    => $wallet,
            'member'    => $member,
            'logs'      => $logsArr,
            'withdraws' => $withdraws,
        ]);
    }

    /**
     * 余额调整（POST：member_id / amount 正负 / note）
     */
    public function adjust()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $memberId = (int)input('post.member_id', 0);
        $amount = round((float)input('post.amount', 0), 2);
        $note = trim((string)input('post.note', ''));
        if ($memberId <= 0 || $amount == 0) return $this->fail('参数错误');
        $wallet = Db::name('wallet')->where('member_id', $memberId)->find();
        if (!$wallet) return $this->fail('该会员暂无钱包');
        $newBalance = round((float)$wallet['balance'] + $amount, 2);
        if ($newBalance < 0) return $this->fail('调整后余额不能为负');
        Db::name('wallet')->where('member_id', $memberId)->update([
            'balance' => $newBalance,
            'total_income' => $amount > 0 ? Db::raw('total_income+' . $amount) : Db::raw('total_income'),
            'update_time' => time(),
        ]);
        Db::name('wallet_log')->insert([
            'member_id' => $memberId, 'type' => 'adjust', 'amount' => $amount,
            'balance_after' => $newBalance, 'note' => $note ?: '后台调整', 'create_time' => time(),
        ]);
        AdminLog::record('wallet_adjust', '钱包调整：会员#' . $memberId . ' ' . ($amount > 0 ? '+' : '') . $amount);
        return $this->ok('已调整', (string)url('admin/wallet/detail', ['member_id' => $memberId]));
    }

    /**
     * 提现列表
     */
    public function withdraw()
    {
        $status = (int)input('status', -1);
        $q = Db::name('withdraw')->alias('w')->field('w.*,m.username,m.nickname')
            ->leftJoin('member m', 'm.id=w.member_id');
        if ($status >= 0) $q->where('w.status', $status);
        $q->order('w.id', 'desc');
        $list = $q->paginate([
            'list_rows' => 15,
            'query'     => ['status' => $status],
        ]);
        return view('wallet/withdraw', [
            'list'  => $list,
            'pages' => $list->render(),
            'cur'   => $status,
        ]);
    }

    /**
     * 提现审核（POST）
     * action=pay 打款：status 0->1，冻结清零，累计提现+金额
     * action=reject 拒绝：status 0->2，冻结回滚到余额
     */
    public function withdraw_handle()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $id = (int)input('post.id', 0);
        $action = (string)input('post.action', '');
        $note = trim((string)input('post.admin_note', ''));
        $wd = Db::name('withdraw')->where('id', $id)->find();
        if (!$wd) return $this->fail('提现记录不存在');
        if ((int)$wd['status'] !== 0) return $this->fail('该申请已处理');

        if ($action == 'pay') {
            Db::name('withdraw')->where('id', $id)->update([
                'status' => 1, 'admin_note' => $note, 'handle_time' => time(),
            ]);
            Db::name('wallet')->where('member_id', $wd['member_id'])->update([
                'frozen' => Db::raw('frozen-' . $wd['amount']),
                'total_withdraw' => Db::raw('total_withdraw+' . $wd['amount']),
                'update_time' => time(),
            ]);
            AdminLog::record('withdraw_pay', '提现打款：#' . $id . ' 会员#' . $wd['member_id'] . ' ¥' . $wd['amount']);
            return $this->ok('已标记打款', (string)url('admin/wallet/withdraw'));
        }
        if ($action == 'reject') {
            Db::name('withdraw')->where('id', $id)->update([
                'status' => 2, 'admin_note' => $note, 'handle_time' => time(),
            ]);
            // 冻结回滚
            Db::name('wallet')->where('member_id', $wd['member_id'])->update([
                'frozen' => Db::raw('frozen-' . $wd['amount']),
                'balance' => Db::raw('balance+' . $wd['amount']),
                'update_time' => time(),
            ]);
            $wallet = Db::name('wallet')->where('member_id', $wd['member_id'])->find();
            Db::name('wallet_log')->insert([
                'member_id' => $wd['member_id'], 'type' => 'adjust', 'amount' => $wd['amount'],
                'balance_after' => $wallet ? $wallet['balance'] : 0,
                'note' => '提现被拒绝，冻结金额退回', 'create_time' => time(),
            ]);
            AdminLog::record('withdraw_reject', '提现拒绝：#' . $id . ' ¥' . $wd['amount']);
            return $this->ok('已拒绝并退回冻结金额', (string)url('admin/wallet/withdraw'));
        }
        return $this->fail('参数错误');
    }
}
