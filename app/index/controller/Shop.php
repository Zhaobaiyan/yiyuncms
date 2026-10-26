<?php
// +----------------------------------------------------------------------
// | 前台商城控制器（v3.12）
// | 商品列表 / 商品详情 / 购物车 / 结算下单 / 支付 / 我的订单
// | 入口：/shop/index  /shop/goods?id=  /shop/cart  /shop/checkout  /shop/order_list  /shop/order_detail?id=
// | 视图：app/index/view/shop/（内置视图，独立样式）
// +----------------------------------------------------------------------
namespace app\index\controller;

use think\facade\Db;
use think\facade\View;
use app\common\service\PayService;

class Shop extends FrontBase
{
    /** 内置视图目录 */
    protected $mv = '';

    protected function initialize()
    {
        parent::initialize();
        $this->mv = root_path() . 'app' . DIRECTORY_SEPARATOR . 'index' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'shop' . DIRECTORY_SEPARATOR;
        View::assign('member', session('member_id') ? session('member') : null);
    }

    /**
     * 商品列表
     */
    public function index()
    {
        $lang = lang_code();
        $tbl  = $lang . '_content';
        if (!preg_match('/^[a-z]{2,5}_content$/', $tbl)) $tbl = 'cn_content';
        $keyword = trim((string)input('get.keyword', ''));
        $q = Db::name($tbl)->alias('c')
            ->field('c.id,c.title,c.thumb,c.description,c.create_time,s.price,s.original_price,s.sales')
            ->join('shop s', "s.lang='{$lang}' AND s.conid=c.id AND s.status=1")
            ->where('c.status', 1);
        if ($keyword !== '') $q->whereLike('c.title', '%' . $keyword . '%');
        $q->order('s.id', 'desc');
        $list = $q->paginate([
            'list_rows' => 12,
            'query'     => ['keyword' => $keyword],
        ]);
        return $this->page('index', [
            'title' => '商品商城',
            'list'  => $list,
            'lang'  => $lang,
            'keyword' => $keyword,
        ]);
    }

    /**
     * 商品详情
     */
    public function goods()
    {
        $id   = (int)input('id', 0);
        $lang = lang_code();
        $tbl  = $lang . '_content';
        if (!preg_match('/^[a-z]{2,5}_content$/', $tbl)) $tbl = 'cn_content';
        $info = Db::name($tbl)->alias('c')
            ->field('c.*,s.price,s.original_price,s.stock,s.sales,s.status as s_status,s.specs')
            ->join('shop s', "s.lang='{$lang}' AND s.conid=c.id")
            ->where('c.id', $id)->find();
        if (!$info || (int)$info['s_status'] !== 1) {
            abort(404, '商品不存在或已下架');
        }
        $ext = json_decode((string)$info['extend'], true);
        // 图片集：优先 extend.piclist（多图），回退 image 单图
        $pics = [];
        if (!empty($ext['piclist'])) {
            if (is_string($ext['piclist'])) $ext['piclist'] = array_filter(array_map('trim', explode(',', $ext['piclist'])));
            $pics = array_values($ext['piclist']);
        }
        if (!$pics && !empty($info['image'])) $pics = [$info['image']];
        if (!$pics && !empty($info['thumb'])) $pics = [$info['thumb']];

        // 购物车数量（同商品同规格合计）
        $cartNum = 0;
        if (session('member_id')) {
            $rows2 = Db::name('cart')->where('member_id', (int)session('member_id'))
                ->where('lang', $lang)->where('conid', $id)->select();
            foreach ($rows2 as $r) $cartNum += (int)$r['num'];
        }
        // 规格模板与 SKU（v3.17）：specs JSON {"颜色":["红","蓝"],"_sku":[{"spec":"颜色:红,尺寸:X","price":199,"stock":50}]}
        $specs = [];
        $skuMap = [];
        if (!empty($info['specs'])) {
            $dec = json_decode($info['specs'], true);
            if (is_array($dec)) {
                $skuList = $dec['_sku'] ?? [];
                unset($dec['_sku']);
                $specs = $dec;
                foreach ($skuList as $s) {
                    if (is_array($s) && !empty($s['spec'])) {
                        $skuMap[$s['spec']] = ['price' => (float)$s['price'], 'stock' => (int)$s['stock']];
                    }
                }
            }
        }
        return $this->page('goods', [
            'title'  => $info['title'],
            'info'   => $info,
            'ext'    => $ext,
            'pics'   => $pics,
            'lang'   => $lang,
            'cart_num' => $cartNum,
            'specs'  => $specs,
            'sku_map' => $skuMap,
        ]);
    }

    /**
     * 加入购物车
     */
    public function cart_add()
    {
        if (!request()->isPost()) return $this->back('非法请求');
        $uid = (int)session('member_id');
        if (!$uid) return $this->back('请先登录后再加入购物车');
        $conid = (int)input('post.conid', 0);
        $num   = max(1, (int)input('post.num', 1));
        $lang  = lang_code();
        // 规格选择（v3.15）：前端传 spec 文本如 "颜色:红,尺寸:XL"
        $spec = trim((string)input('post.spec', ''));
        // 校验商品存在且上架
        $shop = Db::name('shop')->where('lang', $lang)->where('conid', $conid)->where('status', 1)->find();
        if (!$shop) return $this->back('商品不存在或已下架');
        // SKU 级库存校验（v3.17）
        $skuParse = self::parseSpecs((string)$shop['specs']);
        if (!empty($skuParse['tpl'])) {
            if ($spec === '') return $this->back('请选择商品规格');
            if (!isset($skuParse['sku'][$spec])) return $this->back('请选择正确的商品规格');
            $avail = $skuParse['sku'][$spec]['stock'];
        } else {
            $avail = (int)$shop['stock'];
        }
        if ($avail <= 0) return $this->back('该规格商品库存不足');
        if ($num > $avail) return $this->back('该规格库存仅剩 ' . $avail . ' 件');
        $row = Db::name('cart')->where('member_id', $uid)->where('lang', $lang)->where('conid', $conid)->where('spec', $spec)->find();
        if ($row) {
            Db::name('cart')->where('id', $row['id'])->update(['num' => min($row['num'] + $num, 99)]);
        } else {
            Db::name('cart')->insert([
                'member_id' => $uid, 'lang' => $lang, 'conid' => $conid,
                'num' => $num, 'spec' => $spec, 'create_time' => time(),
            ]);
        }
        return json(['code' => 1, 'msg' => '已加入购物车']);
    }

    /**
     * 更新购物车数量 / 删除
     */
    public function cart_update()
    {
        if (!request()->isPost()) return $this->back('非法请求');
        $uid = (int)session('member_id');
        if (!$uid) return $this->back('请先登录');
        $id  = (int)input('post.id', 0);
        $num = max(1, (int)input('post.num', 1));
        if (input('post.action') == 'del') {
            Db::name('cart')->where('id', $id)->where('member_id', $uid)->delete();
        } else {
            Db::name('cart')->where('id', $id)->where('member_id', $uid)->update(['num' => min($num, 99)]);
        }
        return json(['code' => 1, 'msg' => 'ok']);
    }

    /**
     * 购物车
     */
    public function cart()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        $uid  = (int)session('member_id');
        $lang = lang_code();
        $tbl  = $lang . '_content';
        $rows = Db::name('cart')->where('member_id', $uid)->where('lang', $lang)->order('id', 'desc')->select()->toArray();
        $items = [];
        $total = 0;
        // N+1 优化：一次性取回内容与商品，内存组装（原循环内逐条 find）
        $cMap = [];
        $sMap = [];
        if ($rows) {
            $conIds = array_values(array_unique(array_map('intval', array_column($rows, 'conid'))));
            if ($conIds) {
                foreach (Db::name($tbl)->whereIn('id', $conIds)->select()->toArray() as $c) {
                    $cMap[$c['id']] = $c;
                }
                foreach (Db::name('shop')->where('lang', $lang)->whereIn('conid', $conIds)->select()->toArray() as $s) {
                    $sMap[(int)$s['conid']] = $s;
                }
            }
        }
        foreach ($rows as $r) {
            $c = $cMap[$r['conid']] ?? null;
            $s = $sMap[(int)$r['conid']] ?? null;
            if (!$c || !$s || (int)$s['status'] !== 1) continue;
            // SKU 价格（v3.17）
            $price = (float)$s['price'];
            $avail = (int)$s['stock'];
            $skuParse = self::parseSpecs((string)$s['specs']);
            if (!empty($skuParse['tpl']) && !empty($r['spec']) && isset($skuParse['sku'][$r['spec']])) {
                $price = $skuParse['sku'][$r['spec']]['price'] > 0 ? $skuParse['sku'][$r['spec']]['price'] : $price;
                $avail = $skuParse['sku'][$r['spec']]['stock'];
            }
            $sub = $price * $r['num'];
            $total += $sub;
            $items[] = [
                'cart_id' => $r['id'],
                'conid'   => $c['id'],
                'title'   => $c['title'],
                'thumb'   => $c['thumb'],
                'price'   => $price,
                'stock'   => $avail,
                'num'     => $r['num'],
                'spec'    => (string)$r['spec'],
                'subtotal'=> sprintf('%.2f', $sub),
            ];
        }
        return $this->page('cart', [
            'title' => '我的购物车',
            'items' => $items,
            'total' => sprintf('%.2f', $total),
            'lang'  => $lang,
        ]);
    }

    /**
     * 结算页
     */
    public function checkout()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        $uid  = (int)session('member_id');
        $lang = lang_code();
        $tbl  = $lang . '_content';
        $rows = Db::name('cart')->where('member_id', $uid)->where('lang', $lang)->select()->toArray();
        $items = [];
        $total = 0;
        $specsMap = [];
        foreach ($rows as $r) {
            $c = Db::name($tbl)->where('id', $r['conid'])->find();
            $s = Db::name('shop')->where('lang', $lang)->where('conid', $r['conid'])->find();
            if (!$c || !$s || (int)$s['status'] !== 1) continue;
            $spec = (string)$r['spec'];
            // SKU 库存校验
            $skuParse = self::parseSpecs((string)$s['specs']);
            if (!empty($skuParse['tpl'])) {
                if ($spec === '' || !isset($skuParse['sku'][$spec])) return $this->back('「' . $c['title'] . '」请选择正确规格');
                $avail = $skuParse['sku'][$spec]['stock'];
                $itemPrice = $skuParse['sku'][$spec]['price'] > 0 ? $skuParse['sku'][$spec]['price'] : (float)$s['price'];
            } else {
                $avail = (int)$s['stock'];
                $itemPrice = (float)$s['price'];
            }
            if ($avail < (int)$r['num']) return $this->back('「' . $c['title'] . '」库存不足');
            $total += $itemPrice * $r['num'];
            $items[] = [
                'cart_id' => $r['id'], 'conid' => $c['id'], 'title' => $c['title'], 'thumb' => $c['thumb'],
                'price' => $itemPrice, 'num' => $r['num'], 'spec' => $spec,
            ];
            // 规格模板（结算页可选规格组合）
            if (!empty($s['specs'])) {
                $dec = json_decode($s['specs'], true);
                if (is_array($dec)) { unset($dec['_sku']); if ($dec) $specsMap[$c['id']] = $dec; }
            }
        }
        if (!$items) return $this->back('购物车为空');
        return $this->page('checkout', [
            'title'      => '确认订单',
            'items'      => $items,
            'total'      => sprintf('%.2f', $total),
            'pay_test'   => (int)get_config('shop.pay_test', 1),
            'pay_offline'=> (int)get_config('shop.pay_offline', 1),
            'pay_wechat' => (int)get_config('shop.pay_wechat', 1),
            'pay_alipay' => (int)get_config('shop.pay_alipay', 1),
            'specs_map'  => $specsMap,
            'member'     => session('member'),
        ]);
    }

    /**
     * 提交订单
     */
    public function submit()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        if (!request()->isPost()) return $this->back('非法请求');
        $uid  = (int)session('member_id');
        $lang = lang_code();
        $name = trim((string)input('post.name', ''));
        $mobile = trim((string)input('post.mobile', ''));
        $address = trim((string)input('post.address', ''));
        $remark = trim((string)input('post.remark', ''));
        $payType = (string)input('post.pay_type', 'test');
        if ($name === '' || $mobile === '' || $address === '') return $this->back('请填写完整的收货信息');
        if (!in_array($payType, ['test', 'wechat', 'alipay', 'offline'])) return $this->back('支付方式错误');
        if ($payType == 'test' && (int)get_config('shop.pay_test', 1) !== 1) return $this->back('模拟支付已关闭');
        if ($payType == 'offline' && (int)get_config('shop.pay_offline', 1) !== 1) return $this->back('线下转账已关闭');
        if ($payType == 'wechat' && (int)get_config('shop.pay_wechat', 1) !== 1) return $this->back('微信支付已关闭');
        if ($payType == 'alipay' && (int)get_config('shop.pay_alipay', 1) !== 1) return $this->back('支付宝支付已关闭');

        $tbl = $lang . '_content';
        if (!preg_match('/^[a-z]{2,5}_content$/', $tbl)) $tbl = 'cn_content';
        $rows = Db::name('cart')->where('member_id', $uid)->where('lang', $lang)->select()->toArray();
        if (!$rows) return $this->back('购物车为空');
        // 规格映射：cart_id => 提交的规格文本（前端同一表单多商品时以 cart_id 标识）
        $specPost = input('post.spec/a', []);
        $items = [];
        $total = 0;
        // 事务 + 行锁：SKU/商品级库存原子扣减，防超卖（v3.17）
        $err = null;
        Db::transaction(function () use (&$items, &$total, &$err, $rows, $tbl, $lang, $specPost) {
            foreach ($rows as $r) {
                $c = Db::name($tbl)->where('id', $r['conid'])->find();
                $s = Db::name('shop')->where('lang', $lang)->where('conid', $r['conid'])->lock(true)->find();
                if (!$c || !$s || (int)$s['status'] !== 1) { $err = '部分商品已下架'; return; }
                $spec = trim((string)($specPost[$r['id']] ?? ''));
                // 购物车已存规格的行（快照）无需前端重复提交
                if ($spec === '' && (string)$r['spec'] !== '') $spec = (string)$r['spec'];
                $skuParse = self::parseSpecs((string)$s['specs']);
                if (!empty($skuParse['tpl'])) {
                    if ($spec === '' || !isset($skuParse['sku'][$spec])) { $err = '请选择「' . $c['title'] . '」的规格'; return; }
                    $skuStock = $skuParse['sku'][$spec]['stock'];
                    $skuPrice = $skuParse['sku'][$spec]['price'] > 0 ? $skuParse['sku'][$spec]['price'] : (float)$s['price'];
                    if ($skuStock < (int)$r['num']) { $err = '「' . $c['title'] . '」(' . $spec . ') 库存不足'; return; }
                    // 原子扣减 SKU 库存（读改写，行锁保护）
                    $skuList = $skuParse['skuList'];
                    foreach ($skuList as &$one) {
                        if ($one['spec'] === $spec) $one['stock'] = max(0, $one['stock'] - (int)$r['num']);
                    }
                    unset($one);
                    $newSpecs = $skuParse['tpl'];
                    $newSpecs['_sku'] = $skuList;
                    Db::name('shop')->where('id', $s['id'])->update(['specs' => json_encode($newSpecs, JSON_UNESCAPED_UNICODE), 'update_time' => time()]);
                    $price = $skuPrice;
                } else {
                    if ((int)$s['stock'] < (int)$r['num']) { $err = '「' . $c['title'] . '」库存不足'; return; }
                    Db::name('shop')->where('id', $s['id'])->where('stock', '>=', (int)$r['num'])
                        ->update(['stock' => Db::raw('stock-' . (int)$r['num']), 'update_time' => time()]);
                    $price = (float)$s['price'];
                }
                $total += $price * $r['num'];
                $items[] = [
                    'conid' => $c['id'], 'title' => $c['title'], 'pic' => $c['thumb'],
                    'price' => $price, 'num' => $r['num'], 'spec' => $spec,
                ];
            }
        });
        if ($err !== null) return $this->back($err);
        if (!$items) return $this->back('没有可结算的商品');

        $orderNo = 'YY' . date('YmdHis') . mt_rand(1000, 9999);
        $orderId = Db::name('order')->insertGetId([
            'order_no' => $orderNo, 'member_id' => $uid, 'lang' => $lang,
            'total_amount' => $total, 'pay_amount' => $total,
            'status' => 0, 'pay_type' => $payType,
            'name' => $name, 'mobile' => $mobile, 'address' => $address, 'remark' => $remark,
            'create_time' => time(), 'update_time' => time(),
        ]);
        foreach ($items as $it) {
            Db::name('order_product')->insert([
                'order_id' => $orderId, 'conid' => $it['conid'], 'title' => $it['title'],
                'pic' => $it['pic'], 'price' => $it['price'], 'num' => $it['num'], 'spec' => $it['spec'],
            ]);
        }
        Db::name('cart')->where('member_id', $uid)->where('lang', $lang)->delete();
        return json(['code' => 1, 'msg' => '订单提交成功', 'url' => url('shop/pay', ['id' => $orderId])->build()]);
    }

    /**
     * 支付页（真实微信Native/支付宝当面付二维码 + 模拟/线下，v3.17）
     */
    public function pay()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        $id = (int)input('id', 0);
        $order = Db::name('order')->where('id', $id)->where('member_id', (int)session('member_id'))->find();
        if (!$order) return $this->back('订单不存在');
        // 真实支付二维码（有商户号 + 自动模式时生成）
        $qr = '';
        $realErr = '';
        if ((int)$order['status'] === 0) {
            $notifyBase = request()->domain();
            if ($order['pay_type'] === 'wechat' && PayService::wxReady()) {
                $r = PayService::wxNative($order['order_no'], '易云CMS订单' . $order['order_no'], (float)$order['pay_amount'], $notifyBase . '/shop/notify_wechat');
                if ($r['code'] === 1) $qr = $r['code_url'];
                else $realErr = $r['msg'];
            } elseif ($order['pay_type'] === 'alipay' && PayService::aliReady()) {
                $r = PayService::aliPrecreate($order['order_no'], '易云CMS订单' . $order['order_no'], (float)$order['pay_amount']);
                if ($r['code'] === 1) $qr = $r['qr_code'];
                else $realErr = $r['msg'];
            }
        }
        return $this->page('pay', [
            'title' => '订单支付',
            'order' => $order,
            'pay_wechat' => (int)get_config('shop.pay_wechat', 1),
            'pay_alipay' => (int)get_config('shop.pay_alipay', 1),
            'pay_test'   => (int)get_config('shop.pay_test', 1),
            'pay_offline'=> (int)get_config('shop.pay_offline', 1),
            'qr'         => $qr,
            'real_err'   => $realErr,
            'wx_ready'   => PayService::wxReady() ? 1 : 0,
            'ali_ready'  => PayService::aliReady() ? 1 : 0,
        ]);
    }

    /**
     * 模拟支付（测试支付，直接支付成功）
     */
    public function pay_test()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        if (!request()->isPost()) return $this->back('非法请求');
        $id = (int)input('post.id', 0);
        $order = Db::name('order')->where('id', $id)->where('member_id', (int)session('member_id'))->find();
        if (!$order) return $this->back('订单不存在');
        if ((int)$order['status'] !== 0) return $this->back('订单状态不可支付');
        if ((int)get_config('shop.pay_test', 1) !== 1) return $this->back('模拟支付已关闭');
        $this->paySuccess($id, 'test');
        return json(['code' => 1, 'msg' => '支付成功', 'url' => url('shop/order_detail', ['id' => $id])->build()]);
    }

    /**
     * 模拟微信/支付宝收银台支付成功（本地测试模式，无真实商户号）
     * POST /shop/pay_online  id=订单ID channel=wechat|alipay
     */
    public function pay_online()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        if (!request()->isPost()) return $this->back('非法请求');
        $id = (int)input('post.id', 0);
        $channel = (string)input('post.channel', 'wechat');
        $order = Db::name('order')->where('id', $id)->where('member_id', (int)session('member_id'))->find();
        if (!$order) return $this->back('订单不存在');
        if ((int)$order['status'] !== 0) return $this->back('订单状态不可支付');
        if ($channel == 'wechat' && (int)get_config('shop.pay_wechat', 1) !== 1) return $this->back('微信支付已关闭');
        if ($channel == 'alipay' && (int)get_config('shop.pay_alipay', 1) !== 1) return $this->back('支付宝支付已关闭');
        $this->paySuccess($id, $channel == 'wechat' ? 'wechat' : 'alipay');
        return json(['code' => 1, 'msg' => '支付成功', 'url' => url('shop/order_detail', ['id' => $id])->build()]);
    }

    /**
     * 支付成功公共处理：置为已支付(1) + 销量累加
     */
    protected function paySuccess($id, $payType)
    {
        $order = Db::name('order')->where('id', $id)->find();
        if (!$order || (int)$order['status'] !== 0) return;
        Db::name('order')->where('id', $id)->update([
            'status' => 1, 'pay_type' => $payType, 'pay_time' => time(), 'update_time' => time(),
        ]);
        $items = Db::name('order_product')->where('order_id', $id)->select();
        foreach ($items as $p) {
            Db::name('shop')->where('lang', $order['lang'])->where('conid', $p['conid'])
                ->update(['sales' => Db::raw('sales+' . (int)$p['num'])]);
        }
    }

    /* ==================== 真实支付回调与查询（v3.17） ==================== */

    /**
     * 支付状态轮询（AJAX）：返回 0未支付 1已支付
     */
    public function pay_status()
    {
        $id = (int)input('get.id', 0);
        $order = Db::name('order')->where('id', $id)->where('member_id', (int)session('member_id'))->find();
        if (!$order) return json(['code' => 0, 'msg' => '订单不存在']);
        return json(['code' => 1, 'status' => (int)$order['status']]);
    }

    /**
     * 微信支付回调（Native 异步通知）
     */
    public function notify_wechat()
    {
        $raw = file_get_contents('php://input');
        $r = PayService::wxNotifyVerify($raw);
        if ($r['code'] !== 1) {
            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[' . $r['msg'] . ']]></return_msg></xml>', 200, ['Content-Type' => 'text/xml']);
        }
        $order = Db::name('order')->where('order_no', $r['order_no'])->find();
        if ($order && (int)$order['status'] === 0 && (int)$r['total_fee'] === (int)round((float)$order['pay_amount'] * 100)) {
            $this->paySuccess((int)$order['id'], 'wechat');
        }
        return response(PayService::wxNotifySuccess(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * 支付宝回调（异步通知）
     */
    public function notify_alipay()
    {
        $params = input('post.');
        $r = PayService::aliNotifyVerify($params ?: input('get.'));
        if ($r['code'] !== 1) {
            return 'fail';
        }
        $order = Db::name('order')->where('order_no', $r['order_no'])->find();
        if ($order && (int)$order['status'] === 0 && abs((float)$r['amount'] - (float)$order['pay_amount']) < 0.01) {
            $this->paySuccess((int)$order['id'], 'alipay');
        }
        return 'success';
    }

    /**
     * 确认收货（已发货 -> 已完成）
     */
    public function confirm()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        if (!request()->isPost()) return $this->back('非法请求');
        $id = (int)input('post.id', 0);
        $order = Db::name('order')->where('id', $id)->where('member_id', (int)session('member_id'))->find();
        if (!$order) return $this->back('订单不存在');
        if ((int)$order['status'] !== 2) return $this->back('当前状态不可确认收货');
        Db::name('order')->where('id', $id)->update([
            'status' => 3, 'confirm_time' => time(), 'update_time' => time(),
        ]);
        return json(['code' => 1, 'msg' => '已确认收货', 'url' => url('shop/order_detail', ['id' => $id])->build()]);
    }

    /**
     * 申请售后（已完成订单，无进行中售后时可申请）
     */
    public function refund_add()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        if (request()->isPost()) {
            $id = (int)input('post.id', 0);
            $order = Db::name('order')->where('id', $id)->where('member_id', (int)session('member_id'))->find();
            if (!$order) return $this->back('订单不存在');
            if ((int)$order['status'] !== 3) return $this->back('订单完成收货后才可申请售后');
            if ((int)$order['refund_status'] > 0) return $this->back('该订单已有售后记录');
            $type = (string)input('post.type', 'refund_only');
            if (!in_array($type, ['refund_only', 'return_goods'])) return $this->back('售后类型错误');
            $amount = round((float)input('post.amount', 0), 2);
            if ($amount <= 0 || $amount > (float)$order['pay_amount']) return $this->back('退款金额有误（不超过订单实付金额）');
            $reason = trim((string)input('post.reason', ''));
            $desc = trim((string)input('post.desc', ''));
            if ($reason === '') return $this->back('请填写退款原因');
            Db::name('refund')->insert([
                'order_id' => $id, 'member_id' => (int)session('member_id'), 'type' => $type,
                'amount' => $amount, 'reason' => $reason, 'desc' => $desc,
                'status' => 0, 'create_time' => time(), 'update_time' => time(),
            ]);
            Db::name('order')->where('id', $id)->update([
                'refund_status' => 1, 'update_time' => time(),
            ]);
            return json(['code' => 1, 'msg' => '售后申请已提交，等待客服处理', 'url' => url('shop/refund_list')->build()]);
        }
        $id = (int)input('id', 0);
        $order = Db::name('order')->where('id', $id)->where('member_id', (int)session('member_id'))->find();
        if (!$order) return $this->back('订单不存在');
        return $this->page('refund_add', [
            'title' => '申请售后',
            'order' => $order,
            'products' => Db::name('order_product')->where('order_id', $id)->select(),
        ]);
    }

    /**
     * 我的售后列表
     */
    public function refund_list()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        $list = Db::name('refund')->where('member_id', (int)session('member_id'))
            ->order('id', 'desc')->paginate(['list_rows' => 10]);
        $rows = [];
        // N+1 优化：批量取订单号
        $orderIds = [];
        foreach ($list as $r) $orderIds[] = (int)$r['order_id'];
        $orderMap = [];
        if ($orderIds) {
            foreach (Db::name('order')->whereIn('id', array_unique($orderIds))->column('order_no', 'id') as $oid => $ono) {
                $orderMap[(int)$oid] = (string)$ono;
            }
        }
        foreach ($list as $r) {
            $r['order_no'] = $orderMap[(int)$r['order_id']] ?? '';
            $rows[] = $r;
        }
        return $this->page('refund_list', [
            'title' => '我的售后',
            'list'  => $rows,
            'pages' => $list->render(),
        ]);
    }

    /**
     * 我的订单
     */
    public function order_list()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        $status = (int)input('status', -1);
        $q = Db::name('order')->where('member_id', (int)session('member_id'));
        if ($status >= 0) $q->where('status', $status);
        $q->order('id', 'desc');
        $list = $q->paginate(['list_rows' => 10, 'query' => ['status' => $status]]);
        $rows = [];
        // N+1 优化：批量取订单商品
        $orderIds = [];
        foreach ($list as $o) $orderIds[] = (int)$o['id'];
        $pmap = [];
        if ($orderIds) {
            foreach (Db::name('order_product')->whereIn('order_id', array_unique($orderIds))->select()->toArray() as $p) {
                $pmap[(int)$p['order_id']][] = $p;
            }
        }
        foreach ($list as $o) {
            $o['products'] = $pmap[(int)$o['id']] ?? [];
            $rows[] = $o;
        }
        return $this->page('order_list', [
            'title' => '我的订单',
            'list'  => $rows,
            'cur'   => $status,
        ]);
    }

    /**
     * 订单详情
     */
    public function order_detail()
    {
        $res = $this->needLogin();
        if ($res !== null) return $res;
        $id = (int)input('id', 0);
        $order = Db::name('order')->where('id', $id)->where('member_id', (int)session('member_id'))->find();
        if (!$order) return $this->back('订单不存在');
        $products = Db::name('order_product')->where('order_id', $id)->select();
        $refund = Db::name('refund')->where('order_id', $id)->order('id', 'desc')->find();
        return $this->page('order_detail', [
            'title'    => '订单详情',
            'order'    => $order,
            'products' => $products,
            'refund'   => $refund,
            'service_url' => url('service/chat')->build(),
        ]);
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

    /**
     * 解析 specs JSON → ['tpl'=>规格模板(不含_sku), 'sku'=>[spec=>price/stock], 'skuList'=>[原始SKU数组]]
     */
    protected static function parseSpecs($specsJson)
    {
        $out = ['tpl' => [], 'sku' => [], 'skuList' => []];
        $dec = json_decode((string)$specsJson, true);
        if (!is_array($dec) || !$dec) return $out;
        $skuList = $dec['_sku'] ?? [];
        unset($dec['_sku']);
        $out['tpl'] = $dec;
        if (is_array($skuList)) {
            $out['skuList'] = $skuList;
            foreach ($skuList as $s) {
                if (is_array($s) && !empty($s['spec'])) {
                    $out['sku'][$s['spec']] = ['price' => (float)($s['price'] ?? 0), 'stock' => (int)($s['stock'] ?? 0)];
                }
            }
        }
        return $out;
    }

    protected function page($tpl, $data = []): string
    {
        $data['member'] = session('member_id') ? session('member') : null;
        $data['lang']   = lang_code();
        $data['is_wap'] = $this->isWap;
        $data['site_name'] = (string)get_config('site.name', '易云CMS');
        View::assign($data);
        return View::fetch($this->mv . $tpl . '.html');
    }
}
