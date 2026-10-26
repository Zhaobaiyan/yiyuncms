<?php
// +----------------------------------------------------------------------
// | 后台商城管理（v3.12）
// | 商品价格库存 / 订单管理 / 支付设置
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\model\AdminLog;
use think\facade\Db;
use think\facade\View;

class Shop extends AdminBase
{
    /**
     * 商品价格管理（按语言列出内容，行内设置价格/库存/上下架）
     */
    public function product()
    {
        $lang    = (string)input('lang', lang_code());
        $keyword = trim((string)input('keyword', ''));
        $onlyShop = (int)input('shop', 1); // 默认仅显示已配置为售卖商品的内容（is_shop=1）
        // 语言白名单校验（防非法 lang 注入 leftJoin）
        $langsAll = \app\common\model\Lang::where('is_open', 1)->order('sort', 'asc')->column('code');
        if (!in_array($lang, $langsAll, true)) $lang = lang_code();
        $tbl = $lang . '_content';
        // 表名安全校验
        if (!preg_match('/^[a-z]{2,5}_content$/', $tbl)) $tbl = 'cn_content';

        $q = Db::name($tbl)->alias('c')->field('c.id,c.title,c.thumb,c.create_time,c.is_shop,s.price,s.original_price,s.stock,s.sales,s.status as s_status,s.specs')
            ->leftJoin('shop s', 's.lang=\'' . $lang . '\' AND s.conid=c.id');
        if ($onlyShop === 1) $q->where('c.is_shop', 1);
        if ($keyword !== '') $q->whereLike('c.title', '%' . $keyword . '%');
        $q->order('c.id', 'desc');
        $query = ['lang' => $lang, 'shop' => $onlyShop];
        if ($keyword !== '') $query['keyword'] = $keyword;
        $list = $q->paginate([
            'list_rows' => 15,
            'query'     => $query,
        ]);
        return view('shop/product', [
            'list'   => $list,
            'pages'  => $list->render(),
            'lang'   => $lang,
            'langs'  => \app\common\model\Lang::where('is_open', 1)->order('sort', 'asc')->field('code,name')->select()->toArray(),
            'keyword'=> $keyword,
            'only_shop' => $onlyShop,
        ]);
    }

    /**
     * 保存商品价格（含规格模板 + SKU 价格库存 v3.17）
     * specs 传 JSON：{"颜色":["红","蓝"],"尺寸":["X","XL","XXL"]}
     * sku 传 JSON：{"颜色:红,尺寸:X":{"price":"199.00","stock":"50"},...}（可空）
     */
    public function product_save()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $lang  = (string)input('post.lang', lang_code());
        $conid = (int)input('post.conid', 0);
        $price = (float)input('post.price', 0);
        $oprice = (float)input('post.original_price', 0);
        $stock = max(0, (int)input('post.stock', 0));
        $status = (int)input('post.status', 1);
        $specs = trim((string)input('post.specs', ''));
        $sku   = trim((string)input('post.sku', ''));
        if ($conid <= 0 || $price < 0) return $this->fail('参数错误');
        $dec = [];
        // 校验规格模板 JSON
        if ($specs !== '') {
            $dec = json_decode($specs, true);
            if (!is_array($dec) || !$dec) return $this->fail('规格模板格式错误（需为JSON对象）');
        }
        // 合并 SKU 价格库存到 specs JSON（_sku 键）
        $skuArr = [];
        if ($sku !== '') {
            $skuDec = json_decode($sku, true);
            if (!is_array($skuDec)) return $this->fail('SKU数据格式错误');
            foreach ($skuDec as $k => $v) {
                if (!is_array($v)) continue;
                $skuArr[] = [
                    'spec'  => (string)$k,
                    'price' => round((float)($v['price'] ?? 0), 2),
                    'stock' => max(0, (int)($v['stock'] ?? 0)),
                ];
            }
        }
        if ($skuArr) $dec['_sku'] = $skuArr;
        $specs = $dec ? json_encode($dec, JSON_UNESCAPED_UNICODE) : '';

        $row = Db::name('shop')->where('lang', $lang)->where('conid', $conid)->find();
        if ($row) {
            Db::name('shop')->where('id', $row['id'])->update([
                'price' => $price, 'original_price' => $oprice,
                'stock' => $stock, 'status' => $status, 'specs' => $specs, 'update_time' => time(),
            ]);
        } else {
            Db::name('shop')->insert([
                'lang' => $lang, 'conid' => $conid, 'price' => $price, 'original_price' => $oprice,
                'stock' => $stock, 'status' => $status, 'specs' => $specs, 'create_time' => time(), 'update_time' => time(),
            ]);
        }
        AdminLog::record('shop_save', '保存商品价格：' . $lang . '#' . $conid . ' ¥' . $price . (count($skuArr) ? ' SKU' . count($skuArr) . '组' : ''));
        return $this->ok('已保存');
    }

    /**
     * 订单列表
     */
    public function order()
    {
        $status = (int)input('status', -1);
        $q = Db::name('order');
        if ($status >= 0) $q->where('status', $status);
        $q->order('id', 'desc');
        $list = $q->paginate([
            'list_rows' => 15,
            'query'     => ['status' => $status],
        ]);
        $rows = [];
        foreach ($list as $r) {
            $member = Db::name('member')->where('id', $r['member_id'])->find();
            $r['username'] = $member ? $member['username'] : '未知';
            $rows[] = $r;
        }
        return view('shop/order', [
            'list'  => $rows,
            'pages' => $list->render(),
            'cur'   => $status,
        ]);
    }

    /**
     * 订单详情
     */
    public function order_detail()
    {
        $id = (int)input('id', 0);
        $order = Db::name('order')->where('id', $id)->find();
        if (!$order) return $this->fail('订单不存在');
        $member = Db::name('member')->where('id', $order['member_id'])->find();
        $products = Db::name('order_product')->where('order_id', $id)->select();
        $refund = Db::name('refund')->where('order_id', $id)->order('id', 'desc')->find();
        return view('shop/order_detail', [
            'order'    => $order,
            'member'   => $member,
            'products' => $products,
            'refund'   => $refund,
        ]);
    }

    /**
     * 订单状态变更
     */
    public function order_set()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $id = (int)input('post.id', 0);
        $status = (int)input('post.status', 0);
        if (!in_array($status, [1, 2, 3, 4])) return $this->fail('参数错误');
        $order = Db::name('order')->where('id', $id)->find();
        if (!$order) return $this->fail('订单不存在');

        if ($status == 1 && $order['status'] == 0) {
            // 确认收款（线下转账或补确认）
            Db::name('order')->where('id', $id)->update([
                'status' => 1, 'pay_type' => $order['pay_type'] ?: 'offline',
                'pay_time' => time(), 'update_time' => time(),
            ]);
            // 销量累加
            $items = Db::name('order_product')->where('order_id', $id)->select();
            foreach ($items as $p) {
                Db::name('shop')->where('lang', $order['lang'])->where('conid', $p['conid'])
                    ->update(['sales' => Db::raw('sales+' . (int)$p['num'])]);
            }
        } elseif ($status == 2 && (int)$order['status'] == 1) {
            // 发货需通过 ship 方法（带快递信息）
            return $this->fail('发货请填写快递信息（在订单详情操作）');
        } else {
            Db::name('order')->where('id', $id)->update(['status' => $status, 'update_time' => time()]);
        }
        AdminLog::record('order_set', '订单状态变更：' . $order['order_no'] . ' -> ' . $status);
        return $this->ok('已更新', (string)url('admin/shop/order_detail', ['id' => $id]));
    }

    /**
     * 订单发货（v3.15）：待发货 -> 已发货，填写快递公司/单号
     */
    public function order_ship()
    {
        if (!request()->isPost()) return $this->fail('非法请求');
        $id = (int)input('post.id', 0);
        $company = trim((string)input('post.express_company', ''));
        $no = trim((string)input('post.express_no', ''));
        if ($company === '' || $no === '') return $this->fail('请填写快递公司与单号');
        $order = Db::name('order')->where('id', $id)->find();
        if (!$order) return $this->fail('订单不存在');
        if ((int)$order['status'] !== 1) return $this->fail('仅待发货订单可发货');
        Db::name('order')->where('id', $id)->update([
            'status' => 2, 'express_company' => $company, 'express_no' => $no,
            'ship_time' => time(), 'update_time' => time(),
        ]);
        AdminLog::record('order_ship', '订单发货：' . $order['order_no'] . ' 快递' . $company . '/' . $no);
        return $this->ok('已发货', (string)url('admin/shop/order_detail', ['id' => $id]));
    }

    /**
     * 支付设置（v3.17：真实微信/支付宝商户号 + 模拟/线下）
     */
    public function setting()
    {
        if (request()->isPost()) {
            set_config('shop.pay_mode', (int)input('post.pay_mode', 1));
            set_config('shop.pay_test', (int)input('post.pay_test', 1));
            set_config('shop.pay_offline', (int)input('post.pay_offline', 1));
            set_config('shop.pay_wechat', (int)input('post.pay_wechat', 1));
            set_config('shop.pay_alipay', (int)input('post.pay_alipay', 1));
            // 微信真实商户号
            set_config('shop.wx_mchid', trim((string)input('post.wx_mchid', '')));
            set_config('shop.wx_appid', trim((string)input('post.wx_appid', '')));
            set_config('shop.wx_key', trim((string)input('post.wx_key', '')));
            set_config('shop.wx_cert_path', trim((string)input('post.wx_cert_path', '')));
            set_config('shop.wx_key_path', trim((string)input('post.wx_key_path', '')));
            // 支付宝真实商户号
            set_config('shop.ali_appid', trim((string)input('post.ali_appid', '')));
            set_config('shop.ali_private_key', trim((string)input('post.ali_private_key', '')));
            set_config('shop.ali_public_key', trim((string)input('post.ali_public_key', '')));
            AdminLog::record('shop_setting', '修改支付设置（含真实商户号配置）');
            return $this->ok('设置已保存', (string)url('admin/shop/setting'));
        }
        return view('shop/setting', [
            'pay_mode'    => (int)get_config('shop.pay_mode', 1),
            'pay_test'    => (int)get_config('shop.pay_test', 1),
            'pay_offline' => (int)get_config('shop.pay_offline', 1),
            'pay_wechat'  => (int)get_config('shop.pay_wechat', 1),
            'pay_alipay'  => (int)get_config('shop.pay_alipay', 1),
            'wx_mchid'    => (string)get_config('shop.wx_mchid', ''),
            'wx_appid'    => (string)get_config('shop.wx_appid', ''),
            'wx_key'      => (string)get_config('shop.wx_key', ''),
            'wx_cert_path'=> (string)get_config('shop.wx_cert_path', ''),
            'wx_key_path' => (string)get_config('shop.wx_key_path', ''),
            'ali_appid'   => (string)get_config('shop.ali_appid', ''),
            'ali_private_key' => (string)get_config('shop.ali_private_key', ''),
            'ali_public_key'  => (string)get_config('shop.ali_public_key', ''),
        ]);
    }
}
