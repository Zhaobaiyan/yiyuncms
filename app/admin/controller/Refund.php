<?php
// +----------------------------------------------------------------------
// | 后台售后管理（v3.15）
// | 售后退款申请列表 / 详情 / 审核（同意并退款 -> 钱包入账；拒绝 -> 填理由）
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use think\facade\Db;
use think\facade\View;

class Refund extends AdminBase
{
    /**
     * 售后申请列表
     */
    public function index()
    {
        $status = (int)input('status', -1);
        $q = Db::name('refund');
        if ($status >= 0) $q->where('status', $status);
        $q->order('id', 'desc');
        $list = $q->paginate([
            'list_rows' => 15,
            'query'     => ['status' => $status],
        ]);
        $rows = [];
        foreach ($list as $r) {
            $order = Db::name('order')->where('id', $r['order_id'])->find();
            $member = Db::name('member')->where('id', $r['member_id'])->find();
            $r['order_no'] = $order ? $order['order_no'] : '';
            $r['username'] = $member ? $member['username'] : '未知';
            $rows[] = $r;
        }
        return view('refund/index', [
            'list'  => $rows,
            'pages' => $list->render(),
            'cur'   => $status,
        ]);
    }

    /**
     * 售后详情
     */
    public function detail()
    {
        $id = (int)input('id', 0);
        $refund = Db::name('refund')->where('id', $id)->find();
        if (!$refund) return $this->fail('售后记录不存在');
        $order = Db::name('order')->where('id', $refund['order_id'])->find();
        $member = Db::name('member')->where('id', $refund['member_id'])->find();
        $products = Db::name('order_product')->where('order_id', $refund['order_id'])->select();
        $wallet = Db::name('wallet')->where('member_id', $refund['member_id'])->find();
        return view('refund/detail', [
            'refund'   => $refund,
            'order'    => $order,
            'member'   => $member,
            'products' => $products,
            'wallet'   => $wallet,
        ]);
    }

    /**
     * 审核售后
     * action=agree_refund 同意并退款（status 0/1 -> 3，钱包入账）
     * action=agree        仅同意（0 -> 1，等待退货寄回）
     * action=reject       拒绝（0/1 -> 2，填写理由）
     */
    public function handle()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $id = (int)input('post.id', 0);
        $action = (string)input('post.action', '');
        $reply = trim((string)input('post.admin_reply', ''));
        $refund = Db::name('refund')->where('id', $id)->find();
        if (!$refund) return $this->fail('售后记录不存在');

        if ($action == 'agree_refund') {
            if ((int)$refund['status'] === 3) return $this->fail('该售后已退款');
            $order = Db::name('order')->where('id', $refund['order_id'])->find();
            // 真实支付原路退款（v3.17）：订单走真实微信/支付宝且商户就绪时优先调支付平台退款
            $platformMsg = '';
            if ($order && (int)$order['status'] === 1) {
                if ($order['pay_type'] === 'wechat' && \app\common\service\PayService::wxReady()) {
                    $r = \app\common\service\PayService::wxRefund($order['order_no'], 'R' . date('YmdHis') . $id, (float)$order['pay_amount'], (float)$refund['amount']);
                    if ($r['code'] !== 1) return $this->fail('微信原路退款失败：' . $r['msg'] . '（可改为线下处理）');
                    $platformMsg = '（微信原路退款已提交）';
                } elseif ($order['pay_type'] === 'alipay' && \app\common\service\PayService::aliReady()) {
                    $r = \app\common\service\PayService::aliRefund($order['order_no'], 'R' . date('YmdHis') . $id, (float)$refund['amount']);
                    if ($r['code'] !== 1) return $this->fail('支付宝原路退款失败：' . $r['msg'] . '（可改为线下处理）');
                    $platformMsg = '（支付宝原路退款已提交）';
                }
            }
            // 退款入钱包
            $this->walletIncome($refund['member_id'], $refund['amount'], '退款入账（订单' . ($order ? $order['order_no'] : '') . '）' . $platformMsg);
            Db::name('refund')->where('id', $id)->update([
                'status' => 3, 'admin_reply' => $reply, 'handle_time' => time(), 'update_time' => time(),
            ]);
            if ($order) {
                Db::name('order')->where('id', $order['id'])->update([
                    'refund_status' => 3, 'update_time' => time(),
                ]);
            }
            AdminLog::record('refund_handle', '售后退款：#' . $id . ' ¥' . $refund['amount'] . ' 入会员钱包' . $platformMsg);
            return $this->ok('已退款到会员钱包' . $platformMsg, (string)url('admin/refund/detail', ['id' => $id]));
        }
        if ($action == 'agree') {
            if ((int)$refund['status'] !== 0) return $this->fail('当前状态不可操作');
            Db::name('refund')->where('id', $id)->update([
                'status' => 1, 'admin_reply' => $reply, 'handle_time' => time(), 'update_time' => time(),
            ]);
            Db::name('order')->where('id', $refund['order_id'])->update([
                'refund_status' => 2, 'update_time' => time(),
            ]);
            AdminLog::record('refund_handle', '售后同意：#' . $id);
            return $this->ok('已同意（等待退货寄回后可退款）', (string)url('admin/refund/detail', ['id' => $id]));
        }
        if ($action == 'reject') {
            if ((int)$refund['status'] !== 0) return $this->fail('当前状态不可操作');
            if ($reply === '') return $this->fail('拒绝时必须填写理由');
            Db::name('refund')->where('id', $id)->update([
                'status' => 2, 'admin_reply' => $reply, 'handle_time' => time(), 'update_time' => time(),
            ]);
            Db::name('order')->where('id', $refund['order_id'])->update([
                'refund_status' => 4, 'update_time' => time(),
            ]);
            AdminLog::record('refund_handle', '售后拒绝：#' . $id . ' ' . $reply);
            return $this->ok('已拒绝', (string)url('admin/refund/detail', ['id' => $id]));
        }
        return $this->fail('参数错误');
    }

    /**
     * 钱包入账（退款/调整共用）
     */
    protected function walletIncome($memberId, $amount, $note)
    {
        $memberId = (int)$memberId;
        $amount = round((float)$amount, 2);
        $wallet = Db::name('wallet')->where('member_id', $memberId)->find();
        if (!$wallet) {
            Db::name('wallet')->insert([
                'member_id' => $memberId, 'balance' => $amount, 'frozen' => 0,
                'total_income' => $amount, 'total_withdraw' => 0, 'update_time' => time(),
            ]);
            $after = $amount;
        } else {
            Db::name('wallet')->where('member_id', $memberId)->update([
                'balance' => Db::raw('balance+' . $amount),
                'total_income' => Db::raw('total_income+' . $amount),
                'update_time' => time(),
            ]);
            $after = round((float)$wallet['balance'] + $amount, 2);
        }
        Db::name('wallet_log')->insert([
            'member_id' => $memberId, 'type' => 'refund', 'amount' => $amount,
            'balance_after' => $after, 'note' => $note, 'create_time' => time(),
        ]);
    }
}
